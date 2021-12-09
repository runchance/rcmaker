# Manual
http://rcmaker.runchance.com

## LICENSE
Apache2.0

## rcmaker

Github: https://github.com/runchance/rcmaker **帮忙点点小星星哦** 

rcmaker 是一个纯粹为项目而生的PHP框架,支持FPM模式下的apache,nginx,也支持CLI模式下的swoole,workerman.  

内置了作者常用的PHP组件，CLI模式下支持swoole多进程协程Server.  

运用该框架让PHP开发变的简单,开发者可以随意运用各种内置组件简化工作量.  

拒绝过度封装，过度依赖composer,为提升整体框架性能,部分组件已经集成到框架内按需加载. 

rcmaker也支持自定义进程和rpc(CLI模式下).


## 优势
**不需要改动代码即可同时支持CLI和FPM运行方式.**

**一切为了项目,快速上手、快速应用、拒绝过度封装、效率至上、对任何影响效率的代码和行为零容忍.**

**尽可能多的简化常见项目操作，例如应用AutoForm组件让数据的增删改查只需要一个数组就能搞定.**

**应对高并发场景.**

**CLI模式下支持异步和协程.**


## rcmaker能做什么

**1、作为独立的高性能web容器**
> 只要操作系统中装有PHP即可，不依赖nginx、apache等容器即可原生支持HTTP|HTTPS协议对外提供web服务。
> 使用nginx、apache等容器反向代理可以获得第三方容器的高兼容性、丰富的组件。
> CLI模式同时支持workerman和swoole,swoole支持协程模式和异步模式。

**2、作为web容器下的php独立框架**
> 使用nginx、apache作为web容器为项目提供基础框架支持。

**3、自定义进程**
> 定时任务。

> rpc服务。

> web服务。

> websocket服务。

> 物联网、游戏、TCP服务、UDP服务、unix socket服务。

> 其他定制协议服务。

## 压测
虚拟机VM 8核16G 操作系统 Ubuntu 20.04.3 LTS

**CLI 模式无业务压测**
**[workerman]**

![](http://rcmaker.runchance.com/benchmarks/workerman.png)

ab

![](http://rcmaker.runchance.com/benchmarks/ab_cli_workerman.png)

autocannon

![](http://rcmaker.runchance.com/benchmarks/cli_workerman.png)

**CLI 模式无业务压测**
**[swoole]**

![](http://rcmaker.runchance.com/benchmarks/swoole.png)

ab

![](http://rcmaker.runchance.com/benchmarks/ab_cli_swoole.png)

autocannon

![](http://rcmaker.runchance.com/benchmarks/cli_swoole.png)









