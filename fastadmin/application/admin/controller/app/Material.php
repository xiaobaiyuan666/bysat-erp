<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 内部资料
 *
 * @icon fa fa-circle-o
 */
class Material extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Material
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Material();
        $this->view->assign('categoryList', $this->model->getCategoryList());
        $this->view->assign('archiveStatusList', $this->model->getArchiveStatusList());
        $this->view->assign('appProjectList', $this->getAppProjectOptions(false));
        $this->view->assign('ownerAdminList', $this->getStaffOptions(false));
        $this->view->assign('materialList', $this->getMaterialOptions());
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

        $params = $this->prepareMaterialParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_material', 'add', '内部资料', $params, '新增内部资料：' . ($params['title'] ?: '未命名资料'));
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
            $this->view->assign('materialList', $this->getMaterialOptions(true, (int)$row['id']));
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareMaterialParams($params, false);
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
                $this->recordBusinessAudit('app_material', 'edit', '内部资料', array_merge($row->toArray(), $params), '更新内部资料：' . (($params['title'] ?? $row['title']) ?: '未命名资料'));
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

    protected function prepareMaterialParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_material');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillRelationLegacy($params, 'app_material', 'replacement_material_id', 'replacement_material_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['expires_on', 'updated_on'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        if ($isCreate && empty($params['updated_on'])) {
            $params['updated_on'] = date('Y-m-d');
        }

        if (empty($params['archive_status'])) {
            $params['archive_status'] = 'active';
        }

        $this->syncMaterialFileFields($params);

        return $params;
    }

    protected function syncMaterialFileFields(array &$params)
    {
        $downloadUrl = trim((string)($params['download_url'] ?? ''));
        $filePath = trim((string)($params['file_path'] ?? ''));
        $downloadName = trim((string)($params['download_name'] ?? ''));

        if ($downloadUrl === '' && $filePath !== '') {
            $downloadUrl = $filePath;
        }
        if ($filePath === '' && $downloadUrl !== '') {
            $filePath = $downloadUrl;
        }
        if ($downloadName === '' && $downloadUrl !== '') {
            $path = parse_url($downloadUrl, PHP_URL_PATH) ?: $downloadUrl;
            $downloadName = basename($path);
        }

        $params['download_url'] = $downloadUrl;
        $params['file_path'] = $filePath;
        $params['download_name'] = $downloadName;

        if ($downloadUrl === '' && $filePath === '') {
            $params['file_size'] = 0;
            $params['file_mime'] = '';
            return;
        }

        $localPath = $this->resolveMaterialLocalPath($filePath ?: $downloadUrl);
        if ($localPath && is_file($localPath)) {
            $params['file_size'] = (string)filesize($localPath);
            if (function_exists('mime_content_type')) {
                $params['file_mime'] = mime_content_type($localPath) ?: ($params['file_mime'] ?? '');
            }
        } elseif (!isset($params['file_size']) || $params['file_size'] === '') {
            $params['file_size'] = 0;
        }
    }

    protected function resolveMaterialLocalPath($path)
    {
        $path = trim((string)$path);
        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return '';
        }

        $relative = parse_url($path, PHP_URL_PATH) ?: $path;
        $relative = ltrim(str_replace(['/', '\\'], DS, $relative), DS);
        if ($relative === '') {
            return '';
        }

        $fullPath = ROOT_PATH . 'public' . DS . $relative;
        return is_file($fullPath) ? $fullPath : '';
    }

    protected function getMaterialOptions($includeEmpty = true, $excludeId = 0)
    {
        $options = $includeEmpty ? [0 => '未选择'] : [];
        $query = Db::name('app_material')
            ->field('id,title,category')
            ->order('title', 'asc');

        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        $rows = $query->select();
        foreach ($rows as $row) {
            $label = $row['title'];
            if (!empty($row['category'])) {
                $label .= ' / ' . $row['category'];
            }
            $options[(int)$row['id']] = $label;
        }

        return $options;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_material', '内部资料', function ($row) {
            return '删除内部资料：' . ($row['title'] ?: '未命名资料');
        });
    }
}
