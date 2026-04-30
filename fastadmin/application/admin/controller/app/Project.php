<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use think\Db;

/**
 * 项目运营项目
 *
 * @icon fa fa-circle-o
 */
class Project extends Base
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Project
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->ensureProjectTypeSupport();
        $this->model = new \app\admin\model\App\Project();
        $this->view->assign('projectTypeList', $this->model->getProjectTypeList());
        $this->view->assign('lifecycleStageList', $this->model->getLifecycleStageList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('priorityList', $this->model->getPriorityList());
        $this->view->assign('managerAdminList', $this->getStaffOptions());
        $this->view->assign('projectList', $this->getProjectOptions());
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

        $params = $this->preExcludeFields($params);
        $params['project_type'] = (string) ($params['project_type'] ?? 'app');
        $this->fillLegacyId($params, 'app_project');
        $this->fillStaffName($params, 'manager_admin_id', 'manager');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'app_project',
                    'add',
                    '项目运营项目',
                    $params,
                    '新增项目运营项目：' . (($params['app_name'] ?? '') ?: '未命名主体') . ' / ' . (($params['name'] ?? '') ?: '未命名项目')
                );
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \think\exception\PDOException | \Exception $e) {
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

        $params = $this->preExcludeFields($params);
        $params['project_type'] = (string) ($params['project_type'] ?? ($row['project_type'] ?? 'app'));
        $params['legacy_id'] = $row['legacy_id'];
        $this->fillStaffName($params, 'manager_admin_id', 'manager');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'app_project',
                    'edit',
                    '项目运营项目',
                    array_merge($row->toArray(), $params),
                    '更新项目运营项目：' . ((($params['app_name'] ?? $row['app_name']) ?: '未命名主体')) . ' / ' . ((($params['name'] ?? $row['name']) ?: '未命名项目'))
                );
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \think\exception\PDOException | \Exception $e) {
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
        $this->deleteWithAudit($ids, 'app_project', '项目运营项目', function ($row) {
            return '删除项目运营项目：' . (($row['app_name'] ?? '') ?: '未命名主体') . ' / ' . (($row['name'] ?? '') ?: '未命名项目');
        });
    }

    protected function ensureProjectTypeSupport(): void
    {
        if ($this->tableHasColumn('app_project', 'project_type')) {
            return;
        }

        $table = config('database.prefix') . 'app_project';
        Db::execute("ALTER TABLE `{$table}` ADD COLUMN `project_type` varchar(30) NOT NULL DEFAULT 'app' COMMENT '项目类型' AFTER `name`");
        Db::name('app_project')->where('project_type', '')->update(['project_type' => 'app']);
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $fullTable = config('database.prefix') . $table;
        $cache[$cacheKey] = !empty(Db::query("SHOW COLUMNS FROM `{$fullTable}` LIKE '{$column}'"));

        return $cache[$cacheKey];
    }
}
