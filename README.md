# 简介 #

* Myfox是淘宝数据产品部开发的一套应用在OLAP系统上的分布式MySQL集群代理服务. 原始的海量
  数据通常在Hadoop等分布式计算平台上得到了一定程度的计算，然后通过预先定义的分库分表规
  则(route_type)装入不同的mysql服务中；前段应用程序需要从这些分散的mysql中查询数据时，
  通过Myfox代理，实现透明的访问。

* Myfox的典型应用场景是OLAP系统. 数据在大部分时间内是静态的，数据通过后台离线的方式批量
  写入. 需要实时更新的数据我们不建议通过Myfox来实现；Myfox目前也确实没这个能力，可预见的
  未来应该也不会支持这样的需求。

* 您看到的这个源码包只是 Myfox 中负责路由计算和数据装载的部分，提供在线透明查询的SQL代理
  部分我们用Node.js做了重新实现，您可以在另外一个源码包里找到.

# 设计思想 #

* Myfox的数据装载服务本质上是一个基于HTTP协议和MySQL存储的任务队列系统。在淘宝内部，Myfox
  数据运行在几个虚拟机上，数据是在Hadoop平台上计算并且根据Myfox的路由规则进行切分的。然后
  通过Myfox提供的API远程提交数据装载任务。

* Myfox用一个常驻进程来处理外部提交的数据装载任务，或者其他任务。这个进程您可以通过下列命
  令来启动：

```bash
$ nohup /usr/bin/php bin/run.php processor &
```

# 代码结构 #

```bash
 .
 |-- READEME								自述文件
 |-- app									  Myfox自身类代码
 |-- bin									  后台运行脚本的启动命令
 |-- build									build文件，采用phing进行代码build
 |-- build.xml
 |-- etc									  配置文件
 |-- lib									  Myfox无关的PHP Class代码
 |-- release.properties			release模式的properties文件
 |-- resource								部署在Hadoop系统上的shell脚本模版
 |-- test									  测试代码
 `-- www									  htdocs目录, index.php作为统一入口程序
```

# 环境依赖 #

* HTTP服务器，且支持php；推荐nginx + php-fpm;

* php >= 5.3.3, 且需要 mysqli (with mysqlnd)、json、apc、pcntl、ib_binary、ftp和spl支持;

* 元数据和路由库(MySQL)，建议采用M/S结构，支持InnoDB;

* 数据节点(MySQL)，不小于2个. 支持读写账号的分离，支持远程LOAD DATA; 

* 数据文件切分服务器，默认通过ftp进行文件传输，因此需要ftp支持;

# 部署与配置 #

* 我们假设：

> 元数据和路由库MySQL部署在172.0.0.1 (master) 和 172.0.0.2 (slave)上;
> 有两个数据节点（MySQL），分别是192.168.1.1 和 192.168.1.2;
> 所有MySQL通过3306端口对外服务，写账号为db_write，读账号为db_read，密码均为123456;

* mysql -h172.0.0.1 -P3306 -udb_write -p123456 < database.sql

* 配置修改：

整个项目在开发过程中采用phing来进行build，开发、单元测试以及release所用到的不同配置都用
phing来管理。因此，对于配置文件的管理，我仍然建议你采用phing进行.

> etc/myfox.ini 是主配置文件，其中 url.prefix 需要根据你的nginx配置而指定;

> etc/mysql.ini 是元数据和路由库的配置文件，这一数据库实例在代码中以default命名.

* 测试数据装入：

```bash
$ sh bin/gateway_call_myfox_import.sh -tnumsplit_v2 -rthedate=20110610,cid=1 test/unit/resource/numsplit_import_data_file.txt
$ sh bin/gateway_call_myfox_import.sh -tmirror_v2 test/unit/resource/mirror_import_data_file.txt 1
```
# TODO #

* 支持online alter table;

* 根据unique key进行SQL语句优化; [done]

# Contributors #
```
project: myfox
commits: 43
files  : 110
authors: 
  39  aleafs                90.7%
  4  Zhiqiang Zhao          9.3%
```
