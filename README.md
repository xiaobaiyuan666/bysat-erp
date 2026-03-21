# 云帆软件 ERP 控制台

这是一个面向软件公司的轻量 ERP 原型，当前已经从早期原生 PHP 页面重构为：

- 后端：PHP 8 + JSON 文件存储
- 前端：Vue 3 + Element Plus + Vite
- 访问方式：登录后进入单页控制台

当前模块已经覆盖：

- 经营驾驶舱
- 财务中心
- 项目交付
- APP 运营
- APP 运营内的研发联动
- 人员权限
- OpenAI 兼容协议 AI 助手

## 当前能力

### 1. 登录与权限

- 支持真实登录 / 退出登录
- 区分“登录账号”和“当前工作身份”
- 管理员可以切换工作身份做权限模拟
- 所有新增、编辑、删除、状态变更、登录退出都会写入操作日志

### 2. 财务中心

- 资金流水
- 应收应付
- 智能记账
- 附件补传
- AI 辅助分析

### 3. 项目交付

- 项目台账
- 任务清单
- 预算与进度
- 风险和负责人负荷

### 4. APP 运营

- APP 生命周期项目管理
- 里程碑
- 运营周报
- 风险问题
- 研发联动：Bug 修复、功能升级、体验优化、测试待发版

### 5. 人员权限

- 员工账号
- 工号 / 部门 / 岗位 / 联系方式 / 入职时间 / 上级
- 角色权限
- 密码初始化与重置
- 操作日志

## 项目结构

```text
frontend/
  src/
    components/
    layouts/
    router/
    stores/
    views/
public/
  api.php
  index.php
  console/              # 前端构建产物
src/
  auth.php
  default-data.php
  helpers.php
  metrics.php
  operations.php
  storage.php
  tech.php
storage/
  app-data.json
```

## 本地运行

在项目根目录执行：

```powershell
C:\tools\php85\php.exe -S 127.0.0.1:8090 -t public
```

然后访问：

- [http://127.0.0.1:8090/](http://127.0.0.1:8090/)

前端源码改完后，如需重新构建：

```powershell
cd frontend
npm run build
```

## 演示账号

默认管理员：

- 账号：`admin`
- 密码：`Admin@123`

其他演示员工默认密码：

- `Start@123`

当前默认内置了财务、项目、运营、产品、技术等演示账号，登录页可以直接点选。

## AI 模型接入

系统支持 OpenAI 兼容协议。

在控制台里填写：

- Provider Name
- Base URL
- API Key
- Model
- Temperature
- System Prompt

适用于：

- OpenAI 兼容云模型服务
- 自建兼容网关
- 本地兼容服务

## 数据说明

- 首次运行会自动生成 `storage/app-data.json`
- 当前适合原型、演示和单机内部使用
- 多人正式协同时，建议下一步切换到 MySQL

## 建议的下一步

如果继续往正式 ERP 推进，优先级建议是：

1. JSON 切 MySQL
2. 登录安全补齐密码策略、会话超时和部门树
3. 客户 / 合同 / 回款计划打通
4. 审批流、报销、发票、付款单联动
5. 技术协同增加版本发布、测试记录和缺陷统计报表
