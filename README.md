
##Image Filter是什么?
一个php处理图片裁剪，压缩，水印的小代码

##Image Filter有哪些功能？

* 裁剪
* 压缩
* 水印（支持图片和文字水印）

##有问题反馈
在使用中有任何问题，欢迎反馈给我，可以用以下联系方式跟我交流

* 邮件(byron1655#163.com, 把#换成@)
* QQ: 710049654

##示例
```php
 $job = array('scaling'=>['size'=>"300,500"],
 'clipping'=>['position'=>'0,0', 'size'=>'150,50'],
 'watermark'=>['mark'=>'logo.png','position'=>0]
 'imagetext'=>['text'=>'ouropera.net','fontsize'=>'10','fontfamily'=>'msyh']);    	
 $image = new ImageFilter("1.jpg", $job, "1_1.jpg"); 
 $image->outimage();
```
