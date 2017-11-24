# 安装
> 把install目录下文件 code.tgz l2tp_pptp.sh放到同一个目录下
```
chmod 777 l2tp_pptp.sh && l2tp_pptp.sh
```
![](https://raw.githubusercontent.com/wfcz10086/phpvpn/master/jpg/1.jpg)

直接按照提示输入即可


# 安装过程
![](https://raw.githubusercontent.com/wfcz10086/phpvpn/master/jpg/2.jpg)

# 安装结束
![](https://raw.githubusercontent.com/wfcz10086/phpvpn/master/jpg/4.jpg)

# 添加vpn账号密码

打开浏览器 ，输入你的服务器IP


![](https://github.com/wfcz10086/phpvpn/blob/master/jpg/login.jpg?raw=true)
```
输入默认账号：admin

密码:10086

```
> 相关配置文件修改  */var/www/html/Config.php*

```
<?php
$admin_login="admin";
$admin_passwd="10086";
$db_ip="127.0.0.1";
$db_user="root";
$db_passwd="meidi";
?>

```

# 添加账号和登录
![](https://github.com/wfcz10086/phpvpn/blob/master/jpg/index.jpg?raw=true)

![](https://github.com/wfcz10086/phpvpn/blob/master/jpg/add_user.jpg?raw=true)

![](https://github.com/wfcz10086/phpvpn/blob/master/jpg/l2tp.jpg?raw=true)
