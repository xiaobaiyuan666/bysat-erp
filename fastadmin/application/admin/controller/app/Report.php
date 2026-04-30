<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 项目汇报
 *
 * @icon fa fa-file-text-o
 */
class Report extends Base
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Report
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Report();
        $this->view->assign('appProjectList', $this->getTypedProjectOptions(false));
        $this->view->assign('staffList', $this->getStaffOptions(false));
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            return parent::index();
        }

        $this->view->assign('summaryCards', $this->buildSummaryCards());
        return $this->view->fetch();
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

        $params = $this->prepareReportParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'app_report',
                    'add',
                    '项目汇报',
                    $params,
                    '新增项目汇报：' . $this->buildReportAuditSubject($params)
                );
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

        $params = $this->prepareReportParams($params, false);
        $params['legacy_id'] = $row['legacy_id'];

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }

            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'app_report',
                    'edit',
                    '项目汇报',
                    array_merge($row->toArray(), $params),
                    '更新项目汇报：' . $this->buildReportAuditSubject(array_merge($row->toArray(), $params))
                );
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

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_report', '项目汇报', function ($row) {
            return '删除项目汇报：' . $this->buildReportAuditSubject($row);
        });
    }

    protected function prepareReportParams(array $params, bool $isCreate): array
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_report');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['report_date', 'record_created_at', 'record_updated_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        if ($isCreate && empty($params['report_date'])) {
            $params['report_date'] = date('Y-m-d');
        }

        return $params;
    }

    protected function buildSummaryCards(): array
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-6 days'));
        $activeProjectStart = date('Y-m-d', strtotime('-13 days'));

        return [
            [
                'title' => '本周已提交',
                'value' => $this->safeCount(function ($query) use ($weekStart, $today) {
                    $query->where('report_date', 'between', [$weekStart, $today]);
                }),
                'hint' => '最近 7 天已经录入的项目汇报',
                'theme' => 'primary',
            ],
            [
                'title' => '有阻塞汇报',
                'value' => $this->safeCount(function ($query) {
                    $query->where('blockers', '<>', '');
                }),
                'hint' => '需要继续协调资源或处理卡点',
                'theme' => 'danger',
            ],
            [
                'title' => '近两周活跃项目',
                'value' => $this->countDistinctProjects($activeProjectStart, $today),
                'hint' => '过去 14 天内有汇报动作的项目',
                'theme' => 'info',
            ],
            [
                'title' => '本周提交人数',
                'value' => $this->countDistinctOwners($weekStart, $today),
                'hint' => '最近 7 天实际提交汇报的人员',
                'theme' => 'success',
            ],
        ];
    }

    protected function safeCount(?callable $callback = null): int
    {
        $query = Db::name('app_report');
        if ($callback) {
            $callback($query);
        }

        return (int) $query->count();
    }

    protected function countDistinctProjects(string $startDate, string $endDate): int
    {
        return (int) Db::name('app_report')
            ->where('app_project_id', '>', 0)
            ->where('report_date', 'between', [$startDate, $endDate])
            ->group('app_project_id')
            ->count();
    }

    protected function countDistinctOwners(string $startDate, string $endDate): int
    {
        return (int) Db::name('app_report')
            ->where('owner_admin_id', '>', 0)
            ->where('report_date', 'between', [$startDate, $endDate])
            ->group('owner_admin_id')
            ->count();
    }

    protected function getTypedProjectOptions(bool $includeEmpty = true): array
    {
        $options = $includeEmpty ? [0 => '不关联项目'] : [];
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

    protected function buildReportAuditSubject(array $data): string
    {
        $reportDate = trim((string) ($data['report_date'] ?? ''));
        $owner = trim((string) ($data['owner'] ?? ''));

        if ($reportDate === '') {
            $reportDate = '未填写日期';
        }
        if ($owner === '') {
            $owner = '未指定汇报人';
        }

        return $reportDate . ' / ' . $owner;
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
