<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 12/1/16
 * Time: 4:16 AM
 */

namespace RobotUnion\Execution;


abstract class Task implements Runnable, Cancelable, Launcher {

    public $device;
    private $execType;

    private $execution_id;

    /** @var  Notifier */
    private $notifier;

    /** @var  Logger */
    public $logger;

    public $input;

    /** @var  Robot */
    public $robot;

    private $url;

    /**
     * @param $url
     * @param $execType
     * @param $id
     */
    public function initialize($url, $execType, $id) {
        $this->url = $url;
        $this->execType = $execType;
        $this->execution_id = $id;
        $this->logger = new JsonLogger(new HttpNotifier($url . "/" . $execType . "/" . $id));
    }

    function cancel() {
        $this->notifier->notify([]);
        die();
    }

    function execute(){
        $output = $this->run();

        $content = [
            'status' => 'ending',
            'output' => $output
        ];
        $opts = [
            'http' => [
                'method' => 'PATCH',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($content)
            ]
        ];

        file_get_contents(
            $this->getUrl() . "/" . $this->execType . "/" . $this->getExecutionId(),
            false,
            stream_context_create($opts)
        );
    }

    function delegate($task_id, $input)
    {
        $content = [
            'task_id' => $task_id,
            'input' => $input,
            'sync' => true
        ];

        switch($this->getExecType()) {
            case 'executions':
                $content['caller_id'] = $this->getExecutionId();
                break;
            case 'developments':
                $content['development_id'] = $this->getExecutionId();
                break;
            default:
                throw new \LogicException("Invalid execution type " . $this->getExecType());
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($content)
            ]
        ];

        $jsonResp = file_get_contents(
            $this->getUrl() . "/executions",
            false,
            stream_context_create($opts)
        );
        $response = json_decode($jsonResp);
        return $response->data->output;
    }

    /**
     * @param mixed $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * @param mixed $input
     */
    public function setInput($input)
    {
        $this->input = $input;
    }

    /**
     * @param mixed $robot
     */
    public function setRobot($robot)
    {
        $this->robot = $robot;
    }

    public function getExecutionId()
    {
        return $this->execution_id;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getExecType()
    {
        return $this->execType;
    }
}