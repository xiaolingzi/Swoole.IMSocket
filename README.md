# ![xiaolingzi](https://raw.githubusercontent.com/xiaolingzi/Swoole.IMSocket/master/logo.gif) Swool.IMSocket 基于swoole的聊天通讯socket  
更多分享请访问 [https://www.xxling.com](https://www.xxling.com)  
## 运行  
在安装好swoole和php之后，直接运行项目文件夹下的App.php文件  
  
php /{实际目录}/application/Projects/IMSocket/App.php -i e  
  
前台后台运行可以通过SwooleServer.php里面的配置项进行配置  
swoole的相关文档请前往 [http://www.swoole.com](http://www.swoole.com)  
## 消息格式  
客户端消息发送格式如下（注意要加上结束符#$#）：  
身份验证  
{"infoType": 100001,"connectionType": 2,"data":{"token":"","userId":1}}#$#  
实际应用中token改为实际验证的token  
  
双人对话  
{"infoType": 110001,"connectionType": 2,"data":{"userId":1,"toUserId":2,"messageContent":"user message","messageType":1}}#$#  
  
群聊消息  
{"infoType": 110002,"connectionType": 2,"data":{"userId":1,"clubId":1,"messageContent":"club message","messageType":1}}#$#  
  
    
服务端返回消息格式：  
双人对话  
{"code":"N00000","message":"xxx","infoType":110001,"data":{"userId":1,"toUserId":2,"messageContent":"user message","messageType":1,"userName":"test1","avatar":""}}#$#  
  
群聊  
{"code":"N00000","message":"xxx","infoType":110002,"data":{"userId":1,"clubId":1,"messageContent":"club message","messageType":1,"userName":"test1","avatar":""}}#$#  
  
消息主要使用json格式进行传输，传输的内容和格式大家可以自己定义，代码层面做相应修改就好，infoType的含义看项目下的 CommonDefine.php 文件，这里就不多说。  
## 测试
项目目录下有test1.html和test2.html两个文件，分别在两个浏览器中打开，就可以用来简单的测试收发数据了。
  
 更详细说明请访问：[https://www.xxling.com/blog/article/3106.aspx](https://www.xxling.com/blog/article/3106.aspx)
