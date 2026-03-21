<?php

namespace app\admin\library\traits;

use think\Db;
use think\Exception;
use think\exception\PDOException;

trait ErpAuditHelper
{
    protected function getAuditActorSnapshot()
    {
        if (method_exists($this, 'getCurrentActor')) {
            return $this->getCurrentActor();
        }

        $adminId = (int)$this->auth->id;
        $info = $this->auth->getUserInfo($adminId);
        $profile = Db::name('staff_profile')
            ->field('legacy_id,name')
            ->where('admin_id', $adminId)
            ->find();

        return [
            'admin_id' => $adminId,
            'legacy_id' => $profile['legacy_id'] ?? '',
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
        ];
    }

    protected function writeAudit($module, $action, $objectType, $objectLegacyId, $content, $adminId = 0)
    {
        $actor = $this->getAuditActorSnapshot();
        Db::name('staff_audit')->insert([
            'legacy_id' => 'audit_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8),
            'admin_id' => $adminId > 0 ? (int)$adminId : (int)$actor['admin_id'],
            'actor_admin_id' => (int)$actor['admin_id'],
            'actor_name' => $actor['name'],
            'module' => (string)$module,
            'action' => (string)$action,
            'object_type' => (string)$objectType,
            'object_legacy_id' => (string)$objectLegacyId,
            'content' => (string)$content,
            'ip' => (string)$this->request->ip(),
            'useragent' => substr((string)$this->request->server('HTTP_USER_AGENT', ''), 0, 255),
            'happened_at' => date('Y-m-d H:i:s'),
            'createtime' => time(),
            'updatetime' => time(),
        ]);
    }

    protected function recordBusinessAudit($module, $action, $objectType, array $row, $content, $adminIdField = '')
    {
        $adminId = 0;
        if ($adminIdField && !empty($row[$adminIdField])) {
            $adminId = (int)$row[$adminIdField];
        } else {
            foreach (['updated_by_admin_id', 'created_by_admin_id', 'owner_admin_id', 'assignee_admin_id', 'reporter_admin_id'] as $field) {
                if (!empty($row[$field])) {
                    $adminId = (int)$row[$field];
                    break;
                }
            }
        }

        $this->writeAudit(
            $module,
            $action,
            $objectType,
            $row['legacy_id'] ?? '',
            $content,
            $adminId
        );
    }

    protected function deleteWithAudit($ids, $module, $objectType, ?callable $contentBuilder = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }

        $list = $this->model->where($pk, 'in', $ids)->select();
        $count = 0;

        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array)$item;
                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $content = $contentBuilder ? call_user_func($contentBuilder, $row) : ('删除' . $objectType);
                    $this->recordBusinessAudit($module, 'delete', $objectType, $row, $content);
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success();
        }

        $this->error(__('No rows were deleted'));
    }
}
