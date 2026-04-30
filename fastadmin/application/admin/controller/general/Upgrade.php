<?php

namespace app\admin\controller\general;

use app\admin\library\ErpManagedUpdateService;
use app\common\controller\Backend;

class Upgrade extends Backend
{
    protected $service = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->service = new ErpManagedUpdateService();
        $this->assignconfig('upgradeOverviewUrl', url('general/upgrade/overview'));
        $this->assignconfig('upgradeSaveConfigUrl', url('general/upgrade/saveconfig'));
        $this->assignconfig('upgradeCheckUrl', url('general/upgrade/checkupdate'));
        $this->assignconfig('upgradeStartUrl', url('general/upgrade/startupdate'));
        $this->assignconfig('upgradeRollbackUrl', url('general/upgrade/rollback'));
    }

    public function index()
    {
        $payload = json_encode(
            $this->service->overview(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $this->view->assign('overviewPayload', base64_encode((string) $payload));

        return $this->view->fetch();
    }

    public function overview()
    {
        $this->success('加载成功', null, $this->service->overview());
    }

    public function saveconfig()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $config = $this->service->saveConfig([
            'source_mode' => (string) $this->request->post('source_mode', '', 'trim'),
            'owner' => (string) $this->request->post('owner', '', 'trim'),
            'repo' => (string) $this->request->post('repo', '', 'trim'),
            'branch' => (string) $this->request->post('branch', '', 'trim'),
            'release_tag' => (string) $this->request->post('release_tag', '', 'trim'),
            'release_asset_pattern' => (string) $this->request->post('release_asset_pattern', '', 'trim'),
            'package_subdir' => (string) $this->request->post('package_subdir', '', 'trim'),
            'skip_ssl_verify' => (bool) $this->request->post('skip_ssl_verify/d', 0),
            'github_token' => (string) $this->request->post('github_token', '', 'trim'),
        ]);

        $this->success('更新源已保存', null, [
            'config' => $config,
            'warnings' => $this->service->overview()['warnings'],
        ]);
    }

    public function checkupdate()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $data = $this->service->checkForUpdates();
        $message = !empty($data['update_available']) ? '检测到可更新版本' : '当前已经是最新版本';
        $this->success($message, null, $data);
    }

    public function startupdate()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $data = $this->service->performUpdate();
        $data['admin_url'] = './' . $data['admin_entry'];

        $this->success('更新完成', null, $data);
    }

    public function rollback()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $historyIndex = (int) $this->request->post('history_index/d', 0);
        $data = $this->service->performRollback($historyIndex);
        $data['admin_url'] = './' . $data['admin_entry'];

        $this->success('回滚完成', null, $data);
    }
}
