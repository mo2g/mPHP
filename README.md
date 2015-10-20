# mPHP简单PHP开发 for You ！
写在前面：

从2011年开始，我所了解到的PHP开发框架就已经很多了，对于一个PHP初学者而言，很难抉择。

而且每一种框架，或多或少又掺杂了框架作者自己的风格，这让原本简洁、高效的PHP变得过于复杂。

2012年，接触了[ePHP框架](http://www.douban.com/group/ephp)，对框架有了一定的了解，为了深入学习MVC框架，就开始尝试自己写一个[PHP框架](http://mo2g.com/view/17/)。

#主要特性如下：

* 基于MVC思想构建

* 超低耦合

* 不强制命名规范

* 超简单扩展第三发类

* 常用方法集成

* 支持[swoole拓展](http://www.swoole.com/)

#性能测试：

* 原生PHP代码index.php：
```php
<?php
class test {
    public function hi() {
        echo 'hello world';
    }
}

$obj = new test();

$obj->hi();
```

##mPHP框架
* 入口文件index.php：
```php
<?php
define('INIT_MPHP','mo2g.com');//常量值可以随便定义
define('INDEX_PATH',    __DIR__.'/');
define('MPHP_PATH',    realpath(INDEX_PATH.'../../').'/');    //框架根目录

include MPHP_PATH . 'mPHP.php';
$mPHP = mPHP::init();
$mPHP -> run();
```

* 控制器代码indexController.php：
```php
<?php
class indexController {
    public function indexAction() {
        echo 'hello world';
    }
}
```

##ab -n 1000
* 原生PHP + fpm：Requests per second: 2037.52 \[#/sec] (mean)
* mPHP + fpm ：Requests per second: 757.23 \[#/sec] (mean)
* mPHP + swoole ：Requests per second: 2940.05 \[#/sec] (mean)

##ab -c 100 -n 1000
* 原生PHP + fpm：Requests per second: 4125.48 \[#/sec] (mean)
* mPHP + fpm：Requests per second: 1282.61 \[#/sec] (mean)
* mPHP + swoole ：Requests per second: 8397.43 \[#/sec] (mean)
