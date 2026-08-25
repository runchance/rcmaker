# Excel 组件

rcmaker 内置了 Excel 读写组件，入口为：

- `xlsx()`
- `X()`

说明：

- `xlsx()` / `X()` 每次调用都会返回新的读写实例，不会复用上一次导出或读取时的内部状态。
- 写入器对应 `RC\Helper\Xlsx`。
- 读取器对应 `RC\Helper\XlsxReader`。
- 这些入口是全局 helper，不是 request 映射方法。

## 运行依赖

当前实现依赖 PHP 的压缩和 XML 读取能力：

- 写入 `.xlsx` 依赖 `ZipArchive`
- 读取 `.xlsx` 依赖 `XMLReader`

## ExcelWrite 写入组件

1、新建控制器 `./apps/index/controller/test.php`

```php
<?php
namespace app\index\controller;
class test{
    public function excel($req)
    {
                $writer = X();
        $header = array(
          'created'=>'date',
          'product_id'=>'integer',
          'quantity'=>'#,##0',
          'amount'=>'price',
          'description'=>'string',
          'tax'=>'[$$-1009]#,##0.00;[RED]-[$$-1009]#,##0.00',
        );
        $data = array(
            array('2015-01-01',873,1,'44.00','misc','=D2*0.05'),
            array('2015-01-12',324,2,'88.00','none','=D3*0.05'),
        );

        $writer->writeSheetHeader('Sheet1', $header);
        foreach($data as $row){
            $writer->writeSheetRow('Sheet1', $row);
        }
        $filename = public_path().'/example.xlsx';
        $writer->writeToFile($filename);
        return $req->D($filename,'example.xlsx');
    }
}
?>
```

访问 `http://localhost:8680/test/excel` 弹出下载窗口

组件更多使用方法请参考 [PHP_XLSXWriter](https://github.com/mk-j/PHP_XLSXWriter)



## ExcelReader 读取组件

用法

1、新建`./public/demo1.xlsx`

类似下图

![](../../img/excel_demo.jpg)

2、新建控制器 `./apps/index/controller/test.php`

```php
<?php
namespace app\index\controller;
class test{
    public function excel_read1($req)
    {
        $file = public_path(). '/demo1.xlsx';
        //打开excel读取对象
        $excel = X('reader')->open($file);
        //读取所有单元
        $result = $excel->readCells();
        return $req->json($result);
    }
}
?>
```

访问 `http://localhost:8680/test/excel_read1`

返回类似数组

```
Array
(
    [A1] => 'col1'
    [B1] => 'col2'
    [A2] => 111
    [B2] => 'aaa'
    [A3] => 222
    [B3] => 'bbb'
)
```


```php
//读取二维数组中的所有记录
$result = $excel->readRows();
```

返回类似数组

```
Array
(
    [1] => Array
        (
            ['A'] => 'col1'
            ['B'] => 'col2'
        )
    [2] => Array
        (
            ['A'] => 111
            ['B'] => 'aaa'
        )
    [3] => Array
        (
            ['A'] => 222
            ['B'] => 'bbb'
        )
)
```


```php
//读取二维数组中的所有列
$result = $excel->readColumns();
```

返回类似数组

```
Array
(
    [A] => Array
        (
            [1] => 'col1'
            [2] => 111
            [3] => 222
        )

    [B] => Array
        (
            [1] => 'col2'
            [2] => 'aaa'
            [3] => 'bbb'
        )

)
```

```php
//读取记录并将第一列用作键
$result = $excel->readRows(true);
```

返回类似数组

```
Array
(
    [2] => Array
        (
            ['col1'] => 111
            ['col2'] => 'aaa'
        )
    [3] => Array
        (
            ['col1'] => 222
            ['col2'] => 'bbb'
        )
)
```

readRows方法的第二个参数可以指定索引样式

```php
use RC\Helper\XlsxReader;
...
//读取记录并将第一列用作键
$result = $excel->readRows(false, XlsxReader::KEYS_ZERO_BASED);
...
```

返回类似数组

```
Array
(
    [0] => Array
        (
            [0] => 'col1'
            [1] => 'col2'
        )
    [1] => Array
        (
            [0] => 111
            [1] => 'aaa'
        )
    [2] => Array
        (
            [0] => 222
            [1] => 'bbb'
        )
)
```

所有允许的索引样式参数

参数 | 说明
---|---
KEYS_ORIGINAL |	从第1索引行第A列开始 (默认)
KEYS_ROW_ZERO_BASED | 从第0索引行开始
KEYS_COL_ZERO_BASED | 从第0索引列开始
KEYS_ZERO_BASED | 从第0索引行和第0索引列开始 (就像 KEYS_ROW_ZERO_BASED + KEYS_COL_ZERO_BASED)
KEYS_ROW_ONE_BASED | 从第1索引行开始
KEYS_COL_ONE_BASED | 从第1索引列开始
KEYS_ONE_BASED | 从第1索引行和第1索引列开始 (就像 KEYS_ROW_ONE_BASED + KEYS_COL_ONE_BASED)

可以与索引样式组合的附加选项

参数 | 说明
---|---
KEYS_FIRST_ROW | 与第一个参数'true'相同
KEYS_RELATIVE | 区域左上角单元格的索引 (not sheet)
KEYS_SWAP | 交换行和列

```php
use RC\Helper\XlsxReader;
...
//读取记录并将第一列用作键
$result = $excel->readRows(['A' => 'bee', 'B' => 'honey'], XlsxReader::KEYS_FIRST_ROW | XlsxReader::KEYS_ROW_ZERO_BASED);
...
```

返回类似数组,用于列名称映射

```
Array
(
    [0] => Array
        (
            [bee] => 111
            [honey] => 'aaa'
        )

    [1] => Array
        (
            [bee] => 222
            [honey] => 'bbb'
        )

)
```

进阶示例

1、新建`./public/demo2.xlsx`

类似下图

![](../../img/excel_demo2-1.png) ![](../../img/excel_demo2-2.png)

```php
<?php
namespace app\index\controller;
class test{
    public function excel_read1($req)
    {
        $file = public_path(). '/demo2.xlsx';
        //打开excel读取对象
        $excel = X('reader')->open($file);
        $result = [
            'sheets' => $excel->getSheetNames() // 获取所有工作表名称
        ];
        
        $result['#1'] = $excel
            // 选择工作表
            ->selectSheet('Demo1') 
            // 设置第一行包含列键的数据区域
            ->setReadArea('B4:D11', true)  
            // 设置时间格式化
            ->setDateFormat('Y-m-d') 
            // 设置 C 列为 Birthday
            ->readRows(['C' => 'Birthday']); 
        
        // 使用自定义列键读取其他数组
        $columnKeys = ['B' => 'year', 'C' => 'value1', 'D' => 'value2'];
        $result['#2'] = $excel
            ->selectSheet('Demo2', 'B5:D13')
            ->readRows($columnKeys);
        
        $result['#3'] = $excel
            ->setReadArea('F5:H13')
            ->readRows($columnKeys);
        return $req->json($result);
    }
}
?>
```

还可以使用回调方法控制读取过程

```php
<?php
namespace app\index\controller;
class test{
    
    public function excel_read1($req)
    {
        $result = [];
        $callback = function($row, $col, $val)use(&$result)
        {
            // 实现例如
            $result[] = $row.$col.$val;
            // 如果函数返回true则数据读取被中断 
            return false;
        };
        $file = public_path(). '/demo1.xlsx';
        //打开excel读取对象
        $excel = X('reader')->open($file);
        $excel->readSheetCallback($callback);
        
        return $req->json($result);
    }
}
?>
```

## 使用说明

写入器和读取器的使用方式不同：

- 写入器：`X()` 或 `xlsx()` 直接拿到可写对象
- 读取器：`X('reader')` 或 `xlsx('reader')` 后，再调用 `open($file)` 打开文件

示例：

```php
$writer = xlsx();
$reader = xlsx('reader')->open($file);
```

如果你连续导出多个文件，建议每个文件都重新调用一次 `xlsx()` 或 `X()`，不要把同一个实例跨导出流程复用。

## 常用读取能力

`XlsxReader` 当前常用方法包括：

方法 | 说明
---|---
open($file) | 打开一个 xlsx 文件并返回新的读取对象
getSheetNames() | 获取工作表名称列表
selectSheet($name, $areaRange = null, $firstRowKeys = false) | 选择工作表，可同时指定读取区域
setReadArea($areaRange, $firstRowKeys = false) | 设置读取区域
setDateFormat($format) | 设置日期输出格式
readCells() | 读取所有单元格
readRows($columnKeys = null, $indexStyle = null) | 按行读取
readColumns($columnKeys = null, $indexStyle = null) | 按列读取
readSheetCallback($callback, $sheetId = null, $indexStyle = null) | 用回调控制读取过程

## 备注

- 读取器 `open($file)` 会返回新的 `XlsxReader` 实例。
- 写入器适合逐行写出和下载导出；读取器适合按 sheet、按区域、按行列结构读取。
- 文档中的 `X()` 和 `xlsx()` 是等价入口，选择一种统一使用即可。

组件更多使用方法请参考 [fast-excel-reader](https://github.com/aVadim483/fast-excel-reader)











