<?php

namespace app\admin\controller\ai;

use app\admin\library\AiWorkspaceService;
use app\common\controller\Backend;

/**
 * AI 工作台
 *
 * @icon fa fa-comments-o
 */
class Conversation extends Backend
{
    protected $model = null;
    protected $service = null;

    protected $noNeedRight = ['bootstrap', 'ask', 'clear'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Ai\Conversation();
        $this->service = new AiWorkspaceService();
    }

    public function index()
    {
        $initialEntry = [
            'focus' => (string) $this->request->get('focus', '', 'trim'),
            'preset_key' => (string) $this->request->get('preset_key', '', 'trim'),
            'prompt' => (string) $this->request->get('prompt', '', 'trim'),
            'auto_ask' => (int) $this->request->get('auto_ask/d', 0) === 1,
            'setting_id' => (int) $this->request->get('setting_id/d', 0),
        ];

        $this->assignconfig('bootstrapUrl', url('ai/conversation/bootstrap'));
        $this->assignconfig('askUrl', url('ai/conversation/ask'));
        $this->assignconfig('clearUrl', url('ai/conversation/clear'));
        $this->assignconfig('settingIndexUrl', url('ai/setting/index'));
        $this->assignconfig('settingAddUrl', url('ai/setting/add'));
        $this->assignconfig('initialAiEntry', $initialEntry);

        return $this->view->fetch();
    }

    public function bootstrap()
    {
        $this->guardIndexPermission();
        @set_time_limit(60);

        $settingId = (int) $this->request->get('setting_id/d', 0);
        $data = $this->service->getBootstrapData($settingId);
        $this->success('获取成功。', null, $data);
    }

    public function ask()
    {
        $this->guardIndexPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误。');
        }

        $prompt = (string) $this->request->post('prompt', '', 'trim');
        $focus = (string) $this->request->post('focus', 'overview', 'trim');
        $presetKey = (string) $this->request->post('preset_key', '', 'trim');
        $settingId = (int) $this->request->post('setting_id/d', 0);
        $quickMode = (int) $this->request->post('quick_mode/d', 0) === 1;

        $result = $this->service->ask($prompt, $focus, $presetKey, $settingId, [
            'quick_mode' => $quickMode,
        ]);
        if (!$result['ok']) {
            $this->error($result['error'] ?? '发送失败。', null, $result);
        }

        $this->success('发送成功。', null, $result);
    }

    public function clear()
    {
        $this->guardIndexPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误。');
        }

        $this->service->clearConversation();
        $this->success('会话已清空。', null, [
            'messages' => [],
            'suggestions' => [],
        ]);
    }

    protected function guardIndexPermission(): void
    {
        if (!$this->auth->check('ai/conversation/index')) {
            $this->error('你没有权限访问 AI 工作台。');
        }
    }
}
