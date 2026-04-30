# 宝塔部署说明

正式主线目录是 [fastadmin](/C:/Users/Administrator/Documents/New%20project/fastadmin)。

## 一、打正式完整包

在仓库根目录执行：

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-baota.ps1
```

输出文件示例：

```text
bysat-erp-fastadmin-baota-20260326-xxxxxx.zip
```

## 二、打增量 Patch 包

如果要从某个提交基线打补丁包：

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-baota.ps1 -PackageMode patch -BaseRef HEAD~1
```

如果要把当前工作区未提交的改动直接打成 Patch：

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-baota.ps1 -PackageMode patch -BaseRef HEAD
```

输出文件示例：

```text
bysat-erp-fastadmin-patch-20260326-xxxxxx.zip
```

## 三、正式部署推荐

- 新环境首次上线：使用完整正式包
- 小版本更新：优先使用 Patch 包
- 大版本切换：先备份数据库，再用完整包或 Release 包

## 四、宝塔推荐环境

- PHP：`8.1` 或 `8.2`
- MySQL：`5.7+` 或 `8.0+`
- 必要扩展：
  - `pdo_mysql`
  - `curl`
  - `fileinfo`
  - `mbstring`
  - `json`
  - `bcmath`

## 五、站点根目录

必须指向：

```text
/your-site-path/fastadmin/public
```

## 六、安装模式

- `clean`：正式环境推荐，不带业务演示数据
- `demo`：演示体验和培训使用

## 七、包内已附带

- Web 安装器 [install.php](/C:/Users/Administrator/Documents/New%20project/fastadmin/public/install.php)
- CLI 安装器 [install-cli.php](/C:/Users/Administrator/Documents/New%20project/fastadmin/deploy/baota/install-cli.php)
- 首次使用说明 [首次使用说明.md](/C:/Users/Administrator/Documents/New%20project/fastadmin/docs/%E9%A6%96%E6%AC%A1%E4%BD%BF%E7%94%A8%E8%AF%B4%E6%98%8E.md)
- 正式部署与升级说明 [正式部署与升级说明.md](/C:/Users/Administrator/Documents/New%20project/fastadmin/docs/%E6%AD%A3%E5%BC%8F%E9%83%A8%E7%BD%B2%E4%B8%8E%E5%8D%87%E7%BA%A7%E8%AF%B4%E6%98%8E.md)

## 八、在线更新

后台入口：

```text
系统资料 -> 在线更新
```

当前支持：

- 远端检查更新
- 自动代码备份
- 数据库 SQL 自动备份
- 数据库迁移账本
- 一键回滚

## 九、正式环境注意事项

- 正式环境上线前，建议先做一次宝塔数据库快照
- 已发布的迁移文件不要直接改内容
- 需要变更时新增新的 migration id
- Patch 包适合小改动，结构性升级仍建议用完整包
