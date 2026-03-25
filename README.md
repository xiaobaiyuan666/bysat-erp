# bysat-erp

当前正式主线为 [fastadmin](/C:/Users/Administrator/Documents/New%20project/fastadmin)。

旧的根目录 `PHP + Vue + JSON` 原型已经从仓库移除，后续开发、运行和交付都以 `fastadmin` 目录为准。

## 项目结构

```text
fastadmin/              FastAdmin ERP 主系统
start-fastadmin.ps1     本地启动脚本
stop-fastadmin.ps1      本地停止脚本
```

## 本地启动

在仓库根目录执行：

```powershell
powershell -ExecutionPolicy Bypass -File .\start-fastadmin.ps1
```

默认会自动选择 `8091-8110` 内的空闲端口。

## 访问地址

启动后访问脚本输出的后台入口，例如：

- `http://127.0.0.1:8091/MWDObBuRlr.php`

## 默认账号

- 管理员：`admin / Admin@123`

## 当前口径

- 正式系统：`fastadmin`
- 废弃系统：仓库根目录旧 `Vue` 原型
- 参考目录：`.references/*` 仅作上游参考，不作为正式运行系统
