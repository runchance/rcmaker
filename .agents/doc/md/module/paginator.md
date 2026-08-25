# 分页组件

rcmaker 内置了分页组件 `RC\Helper\Paginator`。它既可以手动构造，也会被 `SDB()->paginate()` 在 `laravelORM` 和 `medoo` 分页场景下直接返回。

## 手动构造分页器

1、新建控制器 `./apps/index/controller/test.php`

```php
<?php
namespace app\index\controller;
use RC\Helper\Paginator;
class test{
    public function paginator($req)
    {
      $total_items = 1000;
  $items_perPage = 50;
      $current_page = (int)$req->get('page', 1);
      $url_pattern = '/test/Paginator?page=[PAGE]';
      $paginator = new Paginator($total_items, $items_perPage, $current_page, $url_pattern);
      return $req->V('paginator', ['paginator' => $paginator]);
    }
}
?>
```

2、新建模板 `./view/index/paginator.html`

```html
<html>
<head>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
</head>
<body>

<?= $paginator->render(); ?>

</body>
</html>
```

访问 `http://localhost:8680/test/paginator`

效果类似下图

![](../../img/page.png)

`url_pattern` 必须包含占位符 `[PAGE]`，分页组件会在渲染时替换成真实页码。

如果你不想显式调用 `render()`，也可以直接输出对象：

```php
<?= $paginator; ?>
```

## 常用方法

```php
$paginator->render();          // 返回分页 HTML
$paginator->getPages();        // 返回页码数组
$paginator->getPrevUrl();      // 上一页 URL
$paginator->getNextUrl();      // 下一页 URL
$paginator->items();           // 当前页数据
$paginator->hasMorePages();    // 是否还有下一页
$paginator->setPreviousText('上一页');
$paginator->setNextText('下一页');
```

## JSON 输出

`Paginator` 实现了 `JsonSerializable`，直接返回 JSON 时会输出：

- `total`
- `per_page`
- `current_page`
- `last_page`
- `has_more`
- `data`

示例：

```php
<?php
namespace app\index\controller;
use RC\Helper\Paginator;

class test{
  public function paginatorJson($req)
  {
    $paginator = new Paginator(1000, 50, (int)$req->get('page', 1), '/test/paginatorJson?page=[PAGE]', [
      ['id' => 1, 'name' => 'tom'],
      ['id' => 2, 'name' => 'jack'],
    ]);
    return $req->json($paginator);
  }
}
?>
```

## SDB 联动分页

如果你使用的是 `SDB()`，在 `laravelORM` 和 `medoo` 下可以直接调用 `paginate()`：

```php
<?php
namespace app\index\controller;

class test{
  public function page($req)
  {
    $list = $req->SDB()->table('users')->order([['user_id', 'DESC']])->paginate('*', [
      'path' => '/test/page?page=[PAGE]',
      'list_rows' => 15,
      'var_page' => 'page',
      'query' => ['keyword' => $req->get('keyword', '')],
      'fragment' => 'users',
    ]);

    return $req->json([
      'code' => 0,
      'msg' => 'ok',
      'data' => $list->items(),
      'render' => $list->render(),
      'page' => $list,
    ]);
  }
}
?>
```

其中：

- `path`：分页链接模板，必须包含 `[PAGE]`
- `list_rows`：每页数量
- `var_page`：页码参数名
- `query`：需要附加到分页链接上的额外参数
- `fragment`：锚点

## 简单分页

如果你不需要统计总数，可以把 `simple` 设为 `true`。这种模式下不会生成完整页码，只保留上一页/下一页能力：

```php
$list = $req->SDB()->table('users')->paginate('*', [
  'path' => '/test/page?page=[PAGE]',
  'list_rows' => 15,
], true);
```

简单分页下：

- `total` 为 `null`
- `last_page` 为 `null`
- `has_more` 会根据当前页返回条数自动判断
- `render()` 只渲染上一页/下一页
