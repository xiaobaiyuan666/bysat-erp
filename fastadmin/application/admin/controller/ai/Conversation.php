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

    protected $noNeedRight = ['bootstrap', 'ask', 'submit', 'run', 'status', 'clear'];

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
        ];

        $this->assignconfig('bootstrapUrl', url('ai/conversation/bootstrap'));
        $this->assignconfig('askUrl', url('ai/conversation/ask'));
        $this->assignconfig('submitTaskUrl', url('ai/conversation/submit'));
        $this->assignconfig('runTaskUrl', url('ai/conversation/run'));
        $this->assignconfig('taskStatusUrl', url('ai/conversation/status'));
        $this->assignconfig('clearUrl', url('ai/conversation/clear'));
        $this->assignconfig('settingIndexUrl', url('ai/setting/index'));
        $this->assignconfig('initialAiEntry', $initialEntry);

        return $this->view->fetch();
    }

    public function bootstrap()
    {
        $this->guardIndexPermission();
        @set_time_limit(60);

        $data = $this->service->getBootstrapData();
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
        $quickMode = (int) $this->request->post('quick_mode/d', 0) === 1;

        $result = $this->service->ask($prompt, $focus, $presetKey, 0, [
            'quick_mode' => $quickMode,
        ]);
        if (!$result['ok']) {
            $this->error($result['error'] ?? '发送失败。', null, $result);
        }

        $this->success('发送成功。', null, $result);
    }

    public function submit()
    {
        $this->guardIndexPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误。');
        }

        $prompt = (string) $this->request->post('prompt', '', 'trim');
        $focus = (string) $this->request->post('focus', 'overview', 'trim');
        $presetKey = (string) $this->request->post('preset_key', '', 'trim');
        $quickMode = (int) $this->request->post('quick_mode/d', 0) === 1;

        $result = $this->service->submitTask($prompt, $focus, $presetKey, 0, [
            'quick_mode' => $quickMode,
        ]);
        if (!$result['ok']) {
            $this->error($result['error'] ?? '任务提交失败。', null, $result);
        }

        $this->success('任务已提交。', null, $result);
    }

    public function run()
    {
        $this->guardIndexPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误。');
        }

        $taskId = (int) $this->request->post('task_id/d', 0);
        if ($taskId <= 0) {
            $this->error('缺少任务编号。');
        }

        @ignore_user_abort(true);
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
        @set_time_limit(0);

        $result = $this->service->runTask($taskId);
        if (!$result['ok']) {
            $this->error($result['error'] ?? '后台任务执行失败。', null, $result);
        }

        $this->success('后台任务执行完成。', null, $result);
    }

    public function status()
    {
        $this->guardIndexPermission();

        $taskId = (int) $this->request->get('task_id/d', 0);
        if ($taskId <= 0) {
            $this->error('缺少任务编号。');
        }

        $result = $this->service->getTaskStatus($taskId);
        if (!$result['ok']) {
            $this->error($result['error'] ?? '获取任务状态失败。', null, $result);
        }

        $this->success('获取成功。', null, $result);
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
