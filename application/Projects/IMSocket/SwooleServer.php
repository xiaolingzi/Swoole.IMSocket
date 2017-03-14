<?php

namespace Projects\IMSocket;

class SwooleServer
{
    private $serv;

    /**
     * 初始化swoole
     */
    public function __construct()
    {
        $this->serv = new \swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
                'worker_num' => 8,
//                 'daemonize' => true, // 是否作为守护进程
                'max_request' => 10000,
                'dispatch_mode' => 2,
                'debug_mode' => 1,
                'task_worker_num' => 8 
        // 'open_eof_check' => true, //打开EOF检测
        // 'package_eof' => "}\t", //设置EOF
        // 'open_eof_split'=>true, //是否分包
                ));
        $this->serv->on('Start', array(
                $this,
                'onStart' 
        ));
        $this->serv->on('Connect', array(
                $this,
                'onConnect' 
        ));
        $this->serv->on('Receive', array(
                $this,
                'onReceive' 
        ));
        $this->serv->on('Close', array(
                $this,
                'onClose' 
        ));
        $this->serv->on('WorkerStart', array(
                $this,
                'onWorkerStart' 
        ));
        $this->serv->on('Timer', array(
                $this,
                'onTimer' 
        ));
        // bind callback
        $this->serv->on('Task', array(
                $this,
                'onTask' 
        ));
        $this->serv->on('Finish', array(
                $this,
                'onFinish' 
        ));
        
        // 创建消息缓存table
        (new MessageCache())->createDataCacheTable();
        
        $connectionCls=new Connection();
        $connectionCls->createConnectorTable();
        $connectionCls->createCheckTable();
        
        $this->serv->start();
    }

    /**
     * Server启动在主进程的主线程回调此函数
     *
     * @param unknown $serv            
     */
    public function onStart($serv)
    {
        // 设置进程名称
        cli_set_process_title("swoole_im_master");
        echo "Start\n";
    }

    /**
     * 有新的连接进入时，在worker进程中回调
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param int $from_id            
     */
    public function onConnect($serv, $fd, $from_id)
    {
        echo "Client {$fd} connect\n";
        // 将当前连接用户添加到连接池和待检池
        $connectionCls=new Connection();
        $connectionCls->saveConnector($fd);
        $connectionCls->saveCheckConnector($fd);
    }

    /**
     * 接收到数据时回调此函数，发生在worker进程中
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param int $from_id            
     * @param var $data            
     */
    public function onReceive($serv, $fd, $from_id, $data)
    {
        echo "Get Message From Client {$fd}\n";
        
        // send a task to task worker.
        $param = array(
                'fd' => $fd,
                'data' => base64_encode($data) 
        );
        $serv->task(json_encode($param));
        echo "Continue Handle Worker\n";
    }

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param int $from_id            
     */
    public function onClose($serv, $fd, $from_id)
    {
        // 清理缓存数据
        (new MessageCache())->clearCacheData($serv, $fd);
        // 将连接从所在群组中移除，需要先清理群组 先清理连接池的话会找不到id
        (new Group())->removeUserFromGroups($fd);
        // 将连接从连接池中移除
        (new Connection())->removeConnector($fd);
        echo "Client {$fd} close connection\n";
    }

    /**
     * 在task_worker进程内被调用。
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
     * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，
     * 当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task
     *
     * @param swoole_server $serv            
     * @param int $task_id            
     * @param int $from_id            
     * @param
     *            json string $param
     * @return string
     */
    public function onTask($serv, $task_id, $from_id, $param)
    {
        echo "This Task {$task_id} from Worker {$from_id}\n";
        $paramArr = json_decode($param, true);
        $fd = $paramArr['fd'];
        $data = base64_decode($paramArr['data']);
        echo "{$data} ///////\n";
        (new Message())->send($serv, $fd, $data);
        return "Task {$task_id}'s result";
    }

    /**
     * 当worker进程投递的任务在task_worker中完成时，
     * task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程
     *
     * @param swoole_server $serv            
     * @param int $task_id            
     * @param string $data            
     */
    public function onFinish($serv, $task_id, $data)
    {
        echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
    }

    /**
     * 此事件在worker进程/task进程启动时发生
     *
     * @param swoole_server $serv            
     * @param int $worker_id            
     */
    function onWorkerStart($serv, $worker_id)
    {
        echo "onWorkerStart\n";
        
        
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if($worker_id == 0)
        {
            $connectionCls=new Connection();
            // 清除数据
            $connectionCls->clearData();
            echo "clear data finished\n";
            
            // 在Worker进程开启时绑定定时器
            // 低于1.8.0版本task进程不能使用tick/after定时器，所以需要使用$serv->taskworker进行判断
            if(! $serv->taskworker)
            {
                $serv->tick(5000, function ($id)
                {
                    $this->tickerEvent($this->serv);
                });
            }
            else
            {
                $serv->addtimer(5000);
            }
            echo "start timer finished\n";
        }
    }

    /**
     * 定时任务
     *
     * @param swoole_server $serv            
     * @param int $interval            
     */
    public function onTimer($serv, $interval)
    {
        (new Connection())->clearInvalidConnection($serv);
    }

    /**
     * 定时任务
     *
     * @param swoole_server $serv            
     */
    private function tickerEvent($serv)
    {
        (new Connection())->clearInvalidConnection($serv);
    }
    
    
    
}


