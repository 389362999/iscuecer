<?php
/**
* 可逆加密类
* 
* @access private
* @author yaozhen
* @link http://iyaozhen.com
* @version 1.5
*
*/
class Encrypt
{	
	private $cipher = MCRYPT_RIJNDAEL_256; //密码类型,采用相对很安全的AES-256加密
	private $DefaultMode = MCRYPT_MODE_ECB;	//默认加密方式
	private $mode;	//加密模式
	private $DefaultKey = "一段随机生成带字母、数字、特殊字符的字符串";	//默认密钥
	private $key;	//密钥
	private $iv;	//初始向量
	
	function __construct()	//构造函数
	{
		# code...
	}

	/*
	* 设置加密模式（mode值）
	*/
	private function set_mode($mode)
	{
		if(!isset($mode))
		{
			return false;
		}
		else
		{			
			$this->mode = $mode;
		}
	}

	/*
	* 设置密钥（key值）
	*/
	private function set_key($key)
	{
		if(!isset($key))
		{
			return false;
		}
		else
		{
			$key = sha1($key.sha1($salt));	//散列函数多次加密
			$KeySize = $this->get_iv_size();
			$this->key = strlen($key) > $KeySize ? substr($key, 0, $KeySize) : $key;	//如果key值超过范围将进行截取
		}
	}

	/*
	* 获取vi_size
	* @return [int] [可用密钥长度] 
	*/ 
	public function get_iv_size() 
	{  
		if (!isset($this->cipher) || !isset($this->mode)) {  
			return false;
		}

		return mcrypt_get_iv_size($this->cipher, $this->mode);  
	}

	/*
	* 默认加密方法
	* ECB加密模式无需初始化变量iv
	* 使用默认密钥
	*/
	public function default_encrypt($str)
	{
		$this->set_mode($this->DefaultMode);
		$this->set_key($this->DefaultKey);
		$str_encrypt = mcrypt_encrypt($this->cipher,$this->key,$str,$this->mode); //默认加密
		$str_encrypt = base64_encode($str_encrypt);	//使用base64对密文进行编码（利于保存，防止乱码）
		return $str_encrypt;
	}

	/*
	* 默认解密方法
	* ECB加密模式无需初始化变量iv
	* 使用默认密钥
	*/
	public function default_decrypt($str)
	{
		$this->set_mode($this->DefaultMode);
		$this->set_key($this->DefaultKey);
		$str = trim($str);		//去掉两端字符串防止对解密的干扰
		$str_encrypt = base64_decode($str);	//对密文进行解码
		$str_decrypt = mcrypt_decrypt($this->cipher,$this->key,$str_encrypt,$this->mode); //默认解密
		return trim($str_decrypt);	//去掉两端空格，防止解密后的明文出现空格
	}

	/*
	* 一般加密方法
	* $mode：加密模式
	* $key：密钥
	* @return [array] [密文和初始向量]
	*/
	public function encrypt($mode,$key,$str)
	{
		$this->set_mode($mode);
		$this->set_key($key);
		$this->iv = mcrypt_create_iv(mcrypt_get_iv_size($this->cipher,$this->mode),MCRYPT_RAND);//初始化向量
		$str_encrypt = mcrypt_encrypt($this->cipher,$this->key,$str,$this->mode,$this->iv); //加密
		$str_encrypt = base64_encode($str_encrypt);	//使用base64对密文进行编码（利于保存，防止乱码）
		return array('ciphertext' => $str_encrypt, 'iv' => $this->iv);	//返回密文和初始向量
	}

	/*
	* 一般解密方法
	* $mode：加密模式
	* $key：密钥
	* $iv：初始向量
	* @return [string] [明文]
	*/
	public function decrypt($mode,$key,$iv,$str)
	{
		$this->set_mode($mode);
		$this->set_key($key);
		$str = trim($str);		//去掉两端字符串防止对解密的干扰
		$str_decrypt = base64_decode($str);	//先对密文进行解码
		$str_decrypt = mcrypt_decrypt($this->cipher, $this->key, $str_decrypt, $this->mode, $iv);	//解密（需要密钥key和初始向量iv）
		return trim($str_decrypt);
	}

	function __destruct()	//析构函数
	{
		# code...
	}
}
?>