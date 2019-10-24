### 它是什么
- [文档](https://pa-docs.readthedocs.io/zh/latest/)
- 基于 Phalcon 的通用管理后台
- 基本权限，8个粒度，1个字节存储
  - 普通权限（针对用户自己创建的内容）
    - 增删改查，自己的数据
  - 超级权限（针对不是自己的内容）
    - 增删改查，所有数据
- 扩展权限
  - 有管理员分配给角色的扩展权限，比如：
    - 某权限页面的：仓库列表（限制某些角色只能看某哪些仓库的库存），发货提醒按钮等，不同角色权限不同

- 扩展属性
  - 用户可以自己配置的属性，比如：
    - 某权限页面的：显示行数，默认查询条件等，每个用户都有自己的属性配置