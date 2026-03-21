<?php

namespace app\admin\controller\staff;

use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use fast\Random;
use think\Db;

/**
 * 员工档案
 *
 * @icon fa fa-circle-o
 */
class Profile extends Backend
{
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\Staff\Profile
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Staff\Profile();
        $this->view->assign("roleKeyList", $this->model->getRoleKeyList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("managerAdminList", $this->getStaffOptions());
    }

    public function add()
    {
        if (!$this->request->isPost()) {
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $password = trim((string)$this->request->post('password', ''));
        $password = $password !== '' ? $password : 'Start@123';

        Db::startTrans();
        try {
            $params = $this->prepareProfileParams($params, true);
            $adminId = $this->createAdminAccount($params, $password);
            $params['admin_id'] = $adminId;
            $result = $this->model->allowField(true)->save($params);
            $this->syncAdminGroup($adminId, $params['role_key']);
            Db::commit();
        } catch (\think\exception\ValidateException|\think\exception\PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (!$this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $password = trim((string)$this->request->post('password', ''));

        Db::startTrans();
        try {
            $params = $this->prepareProfileParams($params, false);
            $this->updateAdminAccount($row, $params, $password);
            $result = $row->allowField(true)->save($params);
            $this->syncAdminGroup((int)$row['admin_id'], $params['role_key']);
            Db::commit();
        } catch (\think\exception\ValidateException|\think\exception\PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function del($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }

        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $rows = $this->model->where('id', 'in', $ids)->select();
        if (!$rows) {
            $this->error(__('No Results were found'));
        }

        Db::startTrans();
        try {
            foreach ($rows as $row) {
                $adminId = (int)$row['admin_id'];
                if ($adminId === (int)$this->auth->id) {
                    throw new \Exception('不能删除当前登录账号');
                }
                Db::name('auth_group_access')->where('uid', $adminId)->delete();
                Db::name('admin')->where('id', $adminId)->delete();
                $row->delete();
            }
            Db::commit();
        } catch (\think\exception\PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success();
    }

    protected function prepareProfileParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'staff_profile');
        $this->fillStaffLegacy($params, 'manager_admin_id', 'manager_legacy_id');
        if ($isCreate && !isset($params['last_login_at'])) {
            $params['last_login_at'] = null;
        }
        unset($params['admin_id']);
        return $params;
    }

    protected function createAdminAccount(array $params, $password)
    {
        $account = trim((string)$params['account']);
        $this->validateAccount($account);
        if (Db::name('admin')->where('username', $account)->find()) {
            throw new \Exception('登录账号已存在，请更换后再保存');
        }

        $salt = Random::alnum();
        return (int)Db::name('admin')->insertGetId([
            'username'   => $account,
            'nickname'   => $params['name'],
            'password'   => $this->auth->getEncryptPassword($password, $salt),
            'salt'       => $salt,
            'avatar'     => '/assets/img/avatar.png',
            'email'      => $params['email'] ?? '',
            'mobile'     => $params['phone'] ?? '',
            'status'     => $params['status'] === 'active' ? 'normal' : 'hidden',
            'createtime' => time(),
            'updatetime' => time(),
        ]);
    }

    protected function updateAdminAccount($row, array $params, $password)
    {
        $adminId = (int)$row['admin_id'];
        if ($adminId <= 0) {
            throw new \Exception('该员工尚未绑定后台账号');
        }

        $account = trim((string)$params['account']);
        $this->validateAccount($account);
        $exists = Db::name('admin')
            ->where('username', $account)
            ->where('id', '<>', $adminId)
            ->find();
        if ($exists) {
            throw new \Exception('登录账号已存在，请更换后再保存');
        }

        $adminData = [
            'username'   => $account,
            'nickname'   => $params['name'],
            'email'      => $params['email'] ?? '',
            'mobile'     => $params['phone'] ?? '',
            'status'     => $params['status'] === 'active' ? 'normal' : 'hidden',
            'token'      => '',
            'updatetime' => time(),
        ];

        if ($password !== '') {
            $salt = Random::alnum();
            $adminData['salt'] = $salt;
            $adminData['password'] = $this->auth->getEncryptPassword($password, $salt);
        }

        Db::name('admin')->where('id', $adminId)->update($adminData);
    }

    protected function syncAdminGroup($adminId, $roleKey)
    {
        $groupMap = [
            'admin'      => 'Admin group',
            'finance'    => 'ERP 财务组',
            'project'    => 'ERP 项目组',
            'operations' => 'ERP 运营组',
            'service'    => 'ERP 客服组',
            'tech'       => 'ERP 技术组',
            'viewer'     => 'ERP 只读组',
        ];

        $groupName = $groupMap[$roleKey] ?? 'ERP 只读组';
        $groupId = (int)Db::name('auth_group')->where('name', $groupName)->value('id');
        if ($groupId <= 0) {
            throw new \Exception('未找到对应的权限组：' . $groupName);
        }

        Db::name('auth_group_access')->where('uid', $adminId)->delete();
        Db::name('auth_group_access')->insert([
            'uid'      => $adminId,
            'group_id' => $groupId,
        ]);
    }

    protected function validateAccount($account)
    {
        if (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $account)) {
            throw new \Exception('登录账号请使用 3-20 位字母、数字、点、下划线或中划线');
        }
    }
}
