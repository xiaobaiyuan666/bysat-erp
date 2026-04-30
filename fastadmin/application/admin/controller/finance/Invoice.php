<?php

namespace app\admin\controller\finance;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\Config;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 应收应付
 *
 * @icon fa fa-circle-o
 */
class Invoice extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $noNeedRight = ['printview'];

    /**
     * @var \app\admin\model\Finance\Invoice
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Finance\Invoice();
        $this->view->assign('kindList', $this->model->getKindList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('projectList', $this->getProjectOptions());
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

        $params = $this->preExcludeFields($params);
        $actor = $this->getCurrentActor();

        $this->fillLegacyId($params, 'finance_inv');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');

        $params['created_by_legacy_id'] = $actor['legacy_id'];
        $params['created_by_admin_id'] = $actor['admin_id'];
        $params['created_by_name'] = $actor['name'];
        $params['updated_by_legacy_id'] = $actor['legacy_id'];
        $params['updated_by_admin_id'] = $actor['admin_id'];
        $params['updated_by_name'] = $actor['name'];
        $params['record_created_at'] = (!array_key_exists('record_created_at', $params) || !$params['record_created_at']) ? date('Y-m-d H:i:s') : $params['record_created_at'];
        $params['record_updated_at'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('finance_invoice', 'add', '应收应付', $params, '新增账单：' . ($params['title'] ?: '未命名单据') . ' / ' . $params['amount']);
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

        $params = $this->preExcludeFields($params);
        $actor = $this->getCurrentActor();
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');
        $params['updated_by_legacy_id'] = $actor['legacy_id'];
        $params['updated_by_admin_id'] = $actor['admin_id'];
        $params['updated_by_name'] = $actor['name'];
        $params['record_updated_at'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('finance_invoice', 'edit', '应收应付', array_merge($row->toArray(), $params), '更新账单：' . (($params['title'] ?? $row['title']) ?: '未命名单据') . ' / ' . ($params['amount'] ?? $row['amount']));
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
        $this->deleteWithAudit($ids, 'finance_invoice', '应收应付', function ($row) {
            return '删除账单：' . ($row['title'] ?: '未命名单据') . ' / ' . $row['amount'];
        });
    }

    public function printview($ids = null)
    {
        $this->guardReadPermission();

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $this->view->engine->layout(false);
        $this->view->assign([
            'title' => '账单打印预览',
            'row' => $row,
            'kindText' => $this->model->getKindList()[$row['kind']] ?? $row['kind'],
            'statusText' => $this->model->getStatusList()[$row['status']] ?? ($row['status'] ?: '-'),
            'amountText' => $this->formatMoney((float)$row['amount']),
            'attachmentCount' => $this->countAttachments($row['attachment_ids_json'] ?? '[]'),
            'brandInfo' => $this->buildBrandInfo(),
            'printedAt' => date('Y-m-d H:i:s'),
        ]);

        return $this->view->fetch('finance/invoice/printview');
    }

    protected function guardReadPermission(): void
    {
        if (!$this->auth->check('finance/invoice/index')) {
            $this->error('你没有权限访问账单');
        }
    }

    protected function countAttachments($value): int
    {
        if (is_array($value)) {
            return count($value);
        }

        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? count($decoded) : 0;
    }

    protected function formatMoney(float $value): string
    {
        $prefix = $value < 0 ? '-￥' : '￥';
        return $prefix . number_format(abs($value), 2);
    }

    protected function buildBrandInfo(): array
    {
        $siteName = (string)(Config::get('site.name') ?: 'ERP AI 管理系统');

        return [
            'company_name' => $siteName,
            'system_name' => (string)(Config::get('site.login_subtitle') ?: '企业 ERP AI 智能管理系统'),
            'website' => (string)(Config::get('site.site_home_label') ?: Config::get('site.site_home_url') ?: ''),
            'copyright' => (string)(Config::get('site.copyright') ?: Config::get('site.beian') ?: ''),
        ];
    }
}
