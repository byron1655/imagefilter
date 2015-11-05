<?php
/**
 * 样例:example1
 * $job = array('scaling'=>['size'=>"300,500"],
 * 'clipping'=>['position'=>'0,0', 'size'=>'150,50'],
 * 'watermark'=>['mark'=>'logo.png','position'=>0]
 * 'imagetext'=>['text'=>'ouropera.net','fontsize'=>'10','fontfamily'=>'msyh']);
 * $image = new ImageFilter("1.jpg", $job, "1_1.jpg");
 * $image->outimage();
 * 样例:end
 *
 * @authors byron (www.ouropera.net)
 * @date    2015-11-04 11:54:18
 * @version 1.2
 *
 */

class ImageFilter {

	//使用的功能编号,1为图片缩放功能  2为图片裁剪功能   3,为图片加图片水印功能
	private $job_types = array('scaling' => 1, 'clipping' => 2, 'watermark' => 3, 'imagetext' => 4);
	private $marktext = 'www.ouropera.net';
	private $fontfamily = array('arial' => 'arial.ttf', 'msyh' => 'msyh.ttc');
	private $fontsize = 10;
	private $allowFileType = array('gif' => 1, 'jpg' => 2, 'jpeg' => 2, 'png' => 3);
	//1=GIF,2=JPG,3=PNG,4=SWF,5=PSD,6=BMP,7=TIFF(intel byte order),8=TIFF(motorola byte order),9=JPC,10=JP2,11=JPX,12=JB2,13=SWC,14=IFF,15=WBMP,16=XBM
	private $imgtype; //图片的格式
	private $image; //图片资源
	private $width; //图片宽度
	private $height; //图片高度
	private $job; //将要对图片进行处理的工作
	private $scaling_width; //缩放功能:宽度
	private $scaling_height; //缩放功能:高度
	private $position_x; //裁剪功能:x
	private $position_y; //裁剪功能:y
	private $clipping_width; //裁剪功能:宽度
	private $clipping_height; //裁剪功能:高度
	private $sourceaddress;
	private $endaddress; //输出后的地址+文件名
	private $suffix = '_copy'; //词尾后缀
	private $target = array();

	/**
	 * 构造函数
	 * @param [type] $sourceaddress [description]
	 * @param [type] $job           [description]
	 * @param string $endaddress    [description]
	 */
	function __construct($sourceaddress, $job = null, $endaddress = "") {
		$this->sourceaddress = $sourceaddress;
		$this->image = $this->imagesources($sourceaddress);
		if (empty($job) && count($job) == 0) {
			print 'no image filter job to do[0]!';
			exit;
		}
		$this->width = $this->imageWidth();
		$this->height = $this->imageHeight();
		$this->job = $job;
		$this->endaddress = $this->targetImage($endaddress);
	}

	function __destruct() {
		imagedestroy($this->image);
	}

	/**
	 * 检测目标图像后缀
	 * @param  [string] $endaddress 目标地址+文件名
	 * @return [type] 目标地址+文件名
	 */
	private function targetImage($endaddress = "") {
		if (empty($endaddress)) {
			$path = substr($this->sourceaddress, 0, strrpos($this->sourceaddress, '.'));
			$ext = substr($this->sourceaddress, strrpos($this->sourceaddress, '.') + 1);
			$endaddress = $path . $suffix . '.' . $ext;
		} else {
			$path = substr($endaddress, 0, strrpos($endaddress, '.'));
			$ext = substr($endaddress, strrpos($endaddress, '.') + 1);
		}

		$ext = strtolower($ext);

		if (array_key_exists($ext, $this->allowFileType)) {
			$this->target['path'] = $path;
			$this->target['ext'] = $ext;
			$this->target['type'] = $this->allowFileType[$ext];
		} else {
			print 'Invalid file format upload attempt';
			exit;
		}

		return $endaddress;

	}

	function outimage() {
		$i = 0;
		foreach ($this->job_types as $job_name => $job_level) {
			if (isset($this->job[$job_name])) {
				$this->image = $this->$job_name($this->job[$job_name]);
				$i++;
			}
		}
		if ($i > 0) {
			$this->output($this->image);
		} else {
			print 'no match image filter job to do!';
			return false;
		}
	}

	/**
	 * 图片裁剪功能
	 * @param  [obj] $args 参数对象
	 * @return [obj] 返回图像对象
	 */
	private function clipping($args = null) {
		//将传进来的值分别赋给变量
		if (empty($args['position'])) {
			print 'no args:position';
			exit;
		}
		if (empty($args['size'])) {
			print 'no args:size';
			exit;
		}
		list($src_x, $src_y) = explode(",", $args['position']);
		list($dst_w, $dst_h) = explode(",", $args['size']);
		if ($this->width < $src_x + $dst_w || $this->height < $src_y + $dst_h) {
			//这个判断就是限制不能截取到图片外面去
			return false;
		}
		//创建新的画布资源
		$newimg = imagecreatetruecolor($dst_w, $dst_h);
		//进行裁剪
		imagecopyresampled($newimg, $this->image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $dst_w, $dst_h);
		//调用输出方法保存
		return $newimg;
		//$this->output($newimg);
	}

	/**
	 * 图片缩放功能
	 * @param  [obj] $args 参数对象
	 * @return [obj] 返回图像对象
	 */
	private function scaling($args = null) {
		if (empty($args['size'])) {
			print 'no args input!';
			exit;
		}
		list($scaling_width, $scaling_height) = explode(',', $args['size']);

		$this->scaling_width = (int) $scaling_width;
		$this->scaling_height = (int) $scaling_height;

		//获取等比缩放的宽和高
		$this->proimagesize();
		//根据参数进行缩放,并调用输出函数保存处理后的文件
		//$this->output($this->imagescaling());
		return $this->imagescaling();
	}

	/**
	 * 获取图片类型并打开图像资源
	 * @param  [string] $imgad 图像地址
	 * @return [type]        [description]
	 */
	private function imagesources($imgad) {
		$imagearray = $this->getimagearr($imgad);
		switch ($imagearray[2]) {
//1=GIF,2=JPG,3=PNG,4=SWF,5=PSD,6=BMP,7=TIFF(intel byte order),8=TIFF(motorola byte order),9=JPC,10=JP2,11=JPX,12=JB2,13=SWC,14=IFF,15=WBMP,16=XBM
		case 1: //gif
			$this->imgtype = 1;
			$img = imagecreatefromgif($imgad);
			break;
		case 2: //jpeg
			$this->imgtype = 2;
			$img = imagecreatefromjpeg($imgad);
			break;
		case 3: //png
			$this->imgtype = 3;
			$img = imagecreatefrompng($imgad);
			break;
		default:
			return false;
		}
		return $img;
	}

	/**
	 * 加图片水印功能
	 * @param  [obj] $args 参数对象
	 * @return [obj] 返回图像对象
	 */
	private function watermark($args = null) {
		//用函数获取水印文件的长和宽
		$mark_file = $args['mark'];
		$imagearrs = $this->getimagearr($mark_file);

		//调用函数计算出水印加载的位置
		$positionarr = $this->position($args['position'], $imagearrs[0], $imagearrs[1]);

		//加水印
		imagecopy($this->image, $this->imagesources($mark_file), $positionarr[0], $positionarr[1], 0, 0, $imagearrs[0], $imagearrs[1]);

		//调用输出方法保存
		return $this->image;
		//$this->output($this->image);
	}

	private function imagetext($args = null) {

		$text = empty($args['text']) ? $this->$marktext : $args['text'];
		$font = empty($args['fontfamily']) ? $this->fontfamily[0] : $this->fontfamily[$args['fontfamily']];
		$fontsize = empty($args['fontsize']) ? $this->fontsize : (int) $args['fontsize'];

		$tb = imagettfbbox($fontsize, 0, $font, $text);
		// Create some colors
		$white = imagecolorallocate($this->image, 255, 255, 255);
		$grey = imagecolorallocate($this->image, 128, 128, 128);
		$black = imagecolorallocate($this->image, 0, 0, 0);
		// Add some shadow to the text
		imagettftext($this->image, $fontsize, 0, $this->imageWidth() - $tb[2] - 4, $this->imageHeight() - 5, $grey, $font, $text);

		// Add the text
		imagettftext($this->image, $fontsize, 0, $this->imageWidth() - $tb[2] - 5, $this->imageHeight() - 5, $white, $font, $text);
		//imagepng($this->image);
		return $this->image;
	}

	/**
	 * 获得图片宽度
	 * @return [type] [description]
	 */
	private function imageWidth() {
		return imagesx($this->image);
	}

	/**
	 * 获取图片高度
	 * @return [type] [description]
	 */
	private function imageHeight() {
		return imagesy($this->image);
	}

	/**
	 * 计算等比缩放的图片的宽和高
	 * @return [type] [description]
	 */
	private function proimagesize() {
		if ($this->scaling_height && ($this->width < $this->height)) {
//等比缩放算法
			$this->scaling_width = round(($this->scaling_height / $this->height) * $this->width);
		} else {
			$this->scaling_height = round(($this->scaling_width / $this->width) * $this->height);
		}
	}

	/**
	 * 图像缩放功能,返回处理后的图像资源
	 * @return [type] [description]
	 */
	private function imagescaling() {
		$newimg = imagecreatetruecolor($this->scaling_width, $this->scaling_height);

		$tran = imagecolortransparent($this->image); //处理透明算法
		if ($tran >= 0 && $tran < imagecolorstotal($this->image)) {
			$tranarr = imagecolorsforindex($this->image, $tran);
			$newcolor = imagecolorallocate($newimg, $tranarr['red'], $tranarr['green'], $tranarr['blue']);
			imagefill($newimg, 0, 0, $newcolor);
			imagecolortransparent($newimg, $newcolor);
		}

		imagecopyresampled($newimg, $this->image, 0, 0, 0, 0, $this->scaling_width, $this->scaling_height, $this->width, $this->height);
		return $newimg;
	}

	/**
	 * 输出图像
	 * @param  [obj] $image 图像对象
	 * @return [type]        [description]
	 */
	private function output($image) {
		$targetImageType = $this->target['type'];
		switch ($targetImageType) {
		case 1:
			imagegif($image, $this->endaddress);
			break;
		case 2:
			imagejpeg($image, $this->endaddress, 95);
			break;
		case 3:
			imagepng($image, $this->endaddress);
			break;
		default:
			return false;
		}
	}

	/**
	 * 返回图像属性数组方法
	 * @param  [type] $imagesou [description]
	 * @return [type]           [description]
	 */
	private function getimagearr($imagesou) {
		return getimagesize($imagesou);
	}

	/**
	 * 根据传入的数字返回一个位置的坐标,$width和$height分别代表插入图像的宽和高
	 * @param  [int] $num    位置
	 * @param  [int] $width  图像的宽
	 * @param  [int] $height 图像的高
	 * @return [type]         [description]
	 */
	private function position($num, $width, $height) {
//
		switch ($num) {
		case 1:
			$positionarr[0] = 0;
			$positionarr[1] = 0;
			break;
		case 2:
			$positionarr[0] = ($this->width - $width) / 2;
			$positionarr[1] = 0;
			break;
		case 3:
			$positionarr[0] = $this->width - $width;
			$positionarr[1] = 0;
			break;
		case 4:
			$positionarr[0] = 0;
			$positionarr[1] = ($this->height - $height) / 2;
			break;
		case 5:
			$positionarr[0] = ($this->width - $width) / 2;
			$positionarr[1] = ($this->height - $height) / 2;
			break;
		case 6:
			$positionarr[0] = $this->width - $width;
			$positionarr[1] = ($this->height - $height) / 2;
			break;
		case 7:
			$positionarr[0] = 0;
			$positionarr[1] = $this->height - $height;
			break;
		case 8:
			$positionarr[0] = ($this->width - $width) / 2;
			$positionarr[1] = $this->height - $height;
			break;
		case 9:
			$positionarr[0] = $this->width - $width;
			$positionarr[1] = $this->height - $height;
			break;
		case 0:
			$positionarr[0] = rand(0, $this->width - $width);
			$positionarr[1] = rand(0, $this->height - $height);
			break;
		}
		return $positionarr;
	}

}
