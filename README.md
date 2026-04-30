# bysat-erp

当前正式主线为 [fastadmin](/C:/Users/Administrator/Documents/New%20project/fastadmin)。

仓库根目录下早期的 `PHP + Vue + JSON` 原型已经废弃，不再作为正式交付系统。当前开发、部署、打包、安装和在线更新，全部以 `fastadmin` 目录为准。

## 系统说明

- 系统名称：江苏白猿网络科技有限公司 - 猿创软件业务组 - 系统 100% AI 开发
- 官网：<https://www.bysat.com>
- 版权声明：本系统由 AI 参与开发，江苏白猿网络科技有限公司享有 100% 著作权。

## 当前结构

```text
fastadmin/              正式 ERP 主系统
start-fastadmin.ps1     本地启动脚本
stop-fastadmin.ps1      本地停止脚本
scripts/pack-baota.ps1  宝塔正式包 / Patch 包打包脚本
docs/baota-deploy.md    宝塔部署说明
```

## 本地运行

在仓库根目录执行：

```powershell
powershell -ExecutionPolicy Bypass -File .\start-fastadmin.ps1
```

脚本会自动在 `8091` 到 `8110` 之间选择可用端口。

## 默认后台信息

- 示例后台入口：`http://127.0.0.1:8091/MWDObBuRlr.php`
- 默认管理员：`admin / Admin@123`

## 正式打包

生成完整正式包：

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-baota.ps1 -PackageMode full
```

生成 Patch 补丁包：

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-baota.ps1 -PackageMode patch
```

## 安装模式

- `clean`：正式环境推荐，适合首次上线
- `demo`：演示或内部培训使用

## 包内说明文档

正式包内已包含以下文档：

- `fastadmin/docs/首次使用说明.md`
- `fastadmin/docs/正式部署与升级说明.md`
- `fastadmin/docs/正式版发布说明.md`
- `fastadmin/docs/上线检查清单.md`

另外，安装后的站点还可直接访问：

- `/docs/首次使用说明.html`
- `/docs/正式部署与升级说明.html`
