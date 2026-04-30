<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 项目风险与变更
 *
 * @icon fa fa-circle-o
 */
class Risk extends Base
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Risk
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Risk();
        $this->view->assign('typeList', $this->model->getTypeList());
        $this->view->assign('levelList', $this->model->getLevelList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('appProjectList', $this->getTypedProjectOptions(false));
        $this->view->assign('staffList', $this->getStaffOptions(false));
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareRiskParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_risk', 'add', '项目风险与变更', $params, '新增项目风险/变更：' . (($params['title'] ?? '') ?: '未命名事项'));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if (false === $result) {
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

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareRiskParams($params, false);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_risk', 'edit', '项目风险与变更', array_merge($row->toArray(), $params), '更新项目风险/变更：' . ((($params['title'] ?? $row['title']) ?: '未命名事项')));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if (false === $result) {
            $this->error(__('No rows were updated'));
        }

        $this->success();
    }

    protected function prepareRiskParams(array $params, bool $isCreate): array
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_risk');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');

        if (array_key_exists('due_date', $params) && $params['due_date'] === '') {
            $params['due_date'] = null;
        }

        $params['type'] = $params['type'] ?: 'risk';
        $params['level'] = $params['level'] ?: 'medium';
        $params['status'] = $params['status'] ?: 'open';

        if ($isCreate && empty($params['legacy_id'])) {
            $params['legacy_id'] = $this->generateLegacyId('app_risk');
        }

        return $params;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_risk', '项目风险与变更', function ($row) {
            return '删除项目风险/变更：' . (($row['title'] ?? '') ?: '未命名事项');
        });
    }

    protected function getTypedProjectOptions(bool $includeEmpty = true): array
    {
        $options = $includeEmpty ? [0 => '未关联'] : [];
        $fields = ['id', 'app_name', 'name', 'app_version', 'status'];
        if ($this->tableHasColumn('app_project', 'project_type')) {
            $fields[] = 'project_type';
        }

        $rows = Db::name('app_project')
            ->field(implode(',', $fields))
            ->order('status', 'asc')
            ->order('app_name', 'asc')
            ->select();

        $typeMap = [
            'app' => 'APP',
            'miniprogram' => '小程序',
            'website' => '官网/网站',
            'campaign' => '活动投放',
            'private_domain' => '私域运营',
            'other' => '其他',
        ];

        foreach ($rows as $row) {
            $typeText = $typeMap[$row['project_type'] ?? 'app'] ?? '其他';
            $label = '[' . $typeText . '] ' . $row['app_name'];
            if (!empty($row['name'])) {
                $label .= ' / ' . $row['name'];
            }
            if (!empty($row['app_version'])) {
                $label .= ' / ' . $row['app_version'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
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
