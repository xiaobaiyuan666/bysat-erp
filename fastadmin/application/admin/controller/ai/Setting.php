<?php

namespace app\admin\controller\ai;

use app\admin\library\AiProviderDiscovery;
use app\admin\library\AiWorkspaceService;
use app\common\controller\Backend;

class Setting extends Backend
{
    protected $model = null;
    protected $service = null;

    protected $noNeedRight = ['setdefault', 'ping', 'discover', 'applyrecommended'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Ai\Setting();
        $this->service = new AiWorkspaceService();
        $this->assignconfig('conversationIndexUrl', url('ai/conversation/index'));
        $this->assignconfig('discoverUrl', url('ai/setting/discover'));
    }

    public function index()
    {
        $isTableRequest = $this->request->isAjax()
            && ($this->request->has('offset') || $this->request->has('limit') || $this->request->request('keyField'));

        if ($isTableRequest) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $this->request->filter(['strip_tags', 'trim']);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $defaultSetting = $this->service->getDefaultSetting();
            $defaultId = $defaultSetting ? (int) $defaultSetting['id'] : 0;
            $rows = [];

            foreach ($list->items() as $item) {
                $row = $this->service->presentSetting($item->toArray(), true);
                $row['is_default'] = !empty($row['is_default']) || (int) $row['id'] === $defaultId;
                $rows[] = $row;
            }

            return json([
                'total' => $list->total(),
                'rows' => $rows,
            ]);
        }

        return $this->view->fetch();
    }

    public function setdefault($ids = null)
    {
        $this->guardIndexPermission();

        $id = (int) $ids;
        if ($id <= 0) {
            $this->error('请先选择一条模型配置。');
        }

        $setting = $this->service->getSettingById($id);
        if (!$setting) {
            $this->error('模型配置不存在。');
        }

        $this->service->markDefaultSetting($id);
        $this->success('已设为默认模型。');
    }

    public function ping($ids = null)
    {
        $this->guardIndexPermission();

        $id = (int) $ids;
        if ($id <= 0) {
            $this->error('请先选择一条模型配置。');
        }

        $setting = $this->service->getSettingById($id);
        if (!$setting) {
            $this->error('模型配置不存在。');
        }

        $discovery = new AiProviderDiscovery();
        $result = $discovery->discover(
            (string) ($setting['base_url'] ?? ''),
            (string) ($setting['api_key'] ?? ''),
            (string) ($setting['model'] ?? ''),
            [
                'skip_ssl_verify' => !empty($setting['skip_ssl_verify']),
                'timeout' => 10,
                'connect_timeout' => 5,
                'include_root_probe' => false,
            ]
        );

        if (!$result['ok']) {
            $this->error($result['error'] ?? '连接测试失败。');
        }

        if ($result['current_model_found'] === false) {
            $this->error($this->buildMissingModelMessage((string) ($setting['model'] ?? ''), $result));
        }

        $probe = $discovery->probeChatCompletion(
            (string) ($setting['base_url'] ?? ''),
            (string) ($setting['api_key'] ?? ''),
            (string) ($setting['model'] ?? ''),
            [
                'skip_ssl_verify' => !empty($setting['skip_ssl_verify']),
                'timeout' => 15,
                'connect_timeout' => 5,
            ]
        );

        if (!$probe['ok']) {
            $this->error($this->buildProbeFailureMessage((string) ($setting['model'] ?? ''), $probe, $result));
        }

        $result['probe'] = $probe;
        $result['current_model'] = (string) ($setting['model'] ?? '');
        $this->success($this->buildPingSuccessMessage($result), null, $result);
    }

    public function applyrecommended($ids = null)
    {
        $this->guardIndexPermission();

        $id = (int) $ids;
        if ($id <= 0) {
            $this->error('请先选择一条模型配置。');
        }

        $setting = $this->service->getSettingById($id);
        if (!$setting) {
            $this->error('模型配置不存在。');
        }

        if (
            trim((string) ($setting['base_url'] ?? '')) === ''
            || trim((string) ($setting['api_key'] ?? '')) === ''
        ) {
            $this->error('当前模型配置还不完整，请先补齐 Base URL 和 API Key。');
        }

        $discovery = new AiProviderDiscovery();
        $result = $discovery->discover(
            (string) ($setting['base_url'] ?? ''),
            (string) ($setting['api_key'] ?? ''),
            (string) ($setting['model'] ?? ''),
            [
                'skip_ssl_verify' => !empty($setting['skip_ssl_verify']),
                'timeout' => 12,
                'connect_timeout' => 5,
                'include_root_probe' => false,
            ]
        );

        if (!$result['ok']) {
            $this->error($result['error'] ?? '推荐模型检测失败。');
        }

        $recommendedModel = trim((string) ($result['recommended_model'] ?? ''));
        if ($recommendedModel === '') {
            $this->error('当前接口没有识别出更合适的推荐模型。');
        }

        if (strcasecmp($recommendedModel, (string) ($setting['model'] ?? '')) === 0) {
            $this->success('当前模型已经是推荐项，无需切换。');
        }

        $updateData = [
            'model' => $recommendedModel,
            'updatetime' => time(),
        ];

        if (!empty($result['provider_name'])) {
            $updateData['provider_name'] = (string) $result['provider_name'];
        }
        if (!empty($result['normalized_base_url'])) {
            $updateData['base_url'] = (string) $result['normalized_base_url'];
        }

        $this->model->where('id', $id)->update($updateData);
        $this->success('已切换到推荐快模型：' . $recommendedModel . '。');
    }

    public function discover()
    {
        $this->guardIndexPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误。');
        }

        $baseUrl = (string) $this->request->post('base_url', '', 'trim');
        $apiKey = (string) $this->request->post('api_key', '', 'trim');
        $model = (string) $this->request->post('model', '', 'trim');
        $skipSslVerify = (bool) $this->request->post('skip_ssl_verify/d', 0);

        $discovery = new AiProviderDiscovery();
        $result = $discovery->discover($baseUrl, $apiKey, $model, [
            'skip_ssl_verify' => $skipSslVerify,
            'timeout' => 15,
            'connect_timeout' => 6,
            'include_root_probe' => true,
        ]);

        if (!$result['ok']) {
            $this->error($result['error'] ?? '模型探测失败。');
        }

        $this->success('模型探测成功。', null, $result);
    }

    protected function buildPingSuccessMessage(array $result): string
    {
        $modelCount = count($result['model_ids'] ?? []);
        $providerName = (string) ($result['provider_name'] ?? 'OpenAI Compatible');
        $protocol = (string) ($result['protocol'] ?? 'openai-compatible');
        $elapsed = isset($result['probe']['elapsed']) ? (int) $result['probe']['elapsed'] : 0;

        $parts = [
            '连接正常',
            '协议：' . $protocol,
            '供应商：' . $providerName,
            '发现模型：' . $modelCount . ' 个',
        ];

        if ($elapsed > 0) {
            $parts[] = '模型响应：' . $elapsed . 'ms';
        }

        $recommendedModel = trim((string) ($result['recommended_model'] ?? ''));
        $currentModel = trim((string) ($result['current_model'] ?? ''));
        if ($elapsed >= 4000 && $recommendedModel !== '' && strcasecmp($recommendedModel, $currentModel) !== 0) {
            $parts[] = '推荐快模型：' . $recommendedModel;
        }

        return implode('；', $parts);
    }

    protected function buildMissingModelMessage(string $currentModel, array $result): string
    {
        $modelIds = array_slice($result['model_ids'] ?? [], 0, 5);
        $message = '接口已经连通，但当前模型';
        $message .= $currentModel !== '' ? '“' . $currentModel . '”' : '';
        $message .= '不在可用列表里。';

        if ($modelIds) {
            $message .= ' 你可以先改成：' . implode('、', $modelIds) . '。';
        }

        return $message;
    }

    protected function buildProbeFailureMessage(string $currentModel, array $probe, array $discovery): string
    {
        $message = '接口已经连通，但当前模型';
        $message .= $currentModel !== '' ? '“' . $currentModel . '”' : '';
        $message .= '在快速对话测试里失败了。';

        if (!empty($probe['error'])) {
            $message .= ' 失败原因：' . $probe['error'];
        }

        $recommended = array_values(array_filter((array) ($discovery['faster_model_candidates'] ?? [])));

        if ($recommended) {
            $message .= ' 可以先试试：' . implode('、', array_slice($recommended, 0, 3)) . '。';
        }

        return $message;
    }

    protected function guardIndexPermission(): void
    {
        if (!$this->auth->check('ai/setting/index')) {
            $this->error('你没有权限访问 AI 配置。');
        }
    }
}
