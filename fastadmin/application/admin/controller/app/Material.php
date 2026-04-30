<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 项目资料库
 *
 * @icon fa fa-folder-open-o
 */
class Material extends Base
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
        $this->view->assign('appProjectList', $this->getTypedProjectOptions(false));
        $this->view->assign('ownerAdminList', $this->getStaffOptions(false));
        $this->view->assign('materialList', $this->getMaterialOptions());
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
                $this->recordBusinessAudit(
                    'app_material',
                    'add',
                    '项目资料库',
                    $params,
                    '新增项目资料：' . $this->buildMaterialAuditSubject($params)
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
            $this->view->assign('materialList', $this->getMaterialOptions(true, (int) $row['id']));
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
                $this->recordBusinessAudit(
                    'app_material',
                    'edit',
                    '项目资料库',
                    array_merge($row->toArray(), $params),
                    '更新项目资料：' . $this->buildMaterialAuditSubject(array_merge($row->toArray(), $params))
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
        $this->deleteWithAudit($ids, 'app_material', '项目资料库', function ($row) {
            return '删除项目资料：' . $this->buildMaterialAuditSubject($row);
        });
    }

    protected function prepareMaterialParams(array $params, bool $isCreate): array
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

    protected function syncMaterialFileFields(array &$params): void
    {
        $downloadUrl = trim((string) ($params['download_url'] ?? ''));
        $filePath = trim((string) ($params['file_path'] ?? ''));
        $downloadName = trim((string) ($params['download_name'] ?? ''));

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
            $params['file_size'] = (string) filesize($localPath);
            if (function_exists('mime_content_type')) {
                $params['file_mime'] = mime_content_type($localPath) ?: ($params['file_mime'] ?? '');
            }
        } elseif (!isset($params['file_size']) || $params['file_size'] === '') {
            $params['file_size'] = 0;
        }
    }

    protected function resolveMaterialLocalPath($path): string
    {
        $path = trim((string) $path);
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

    protected function getMaterialOptions(bool $includeEmpty = true, int $excludeId = 0): array
    {
        $options = $includeEmpty ? [0 => '不关联替代资料'] : [];
        $query = Db::name('app_material')
            ->field('id,title,category')
            ->order('title', 'asc');

        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        $rows = $query->select();
        foreach ($rows as $row) {
            $label = (string) $row['title'];
            if (!empty($row['category'])) {
                $label .= ' / ' . (string) $row['category'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
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

    protected function buildSummaryCards(): array
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-6 days'));
        $expireSoonEnd = date('Y-m-d', strtotime('+30 days'));

        return [
            [
                'title' => '在用资料',
                'value' => $this->safeCount(function ($query) {
                    $query->where('archive_status', 'active');
                }),
                'hint' => '当前还在项目里直接使用的资料',
                'theme' => 'primary',
            ],
            [
                'title' => '本周更新',
                'value' => $this->safeCount(function ($query) use ($weekStart) {
                    $query->where('updated_on', '>=', $weekStart);
                }),
                'hint' => '最近 7 天更新过的资料条目',
                'theme' => 'info',
            ],
            [
                'title' => '待补附件',
                'value' => $this->safeCount(function ($query) {
                    $query->where('download_url', '')
                        ->where('file_path', '');
                }),
                'hint' => '还没上传文件或下载地址的资料',
                'theme' => 'warning',
            ],
            [
                'title' => '30 天内到期',
                'value' => $this->safeCount(function ($query) use ($today, $expireSoonEnd) {
                    $query->where('archive_status', 'active')
                        ->where('expires_on', 'between', [$today, $expireSoonEnd]);
                }),
                'hint' => '需要确认是否继续生效或更新版本',
                'theme' => 'danger',
            ],
        ];
    }

    protected function safeCount(?callable $callback = null): int
    {
        $query = Db::name('app_material');
        if ($callback) {
            $callback($query);
        }

        return (int) $query->count();
    }

    protected function buildMaterialAuditSubject(array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        $version = trim((string) ($data['version_tag'] ?? ''));

        if ($title === '') {
            $title = '未命名资料';
        }

        return $version === '' ? $title : $title . ' / ' . $version;
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
