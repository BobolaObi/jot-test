<?php

namespace Legacy\Jot\Api;

use Legacy\Jot\Api\Core\RestServer;
use Legacy\Jot\Api\Core\RestView;


class View implements RestView
{

    public $success = true;
    public $message = "";

    public function show(RestServer $rest)
    {
        if ($rest->getRequest()->getURI() == "/") {
            return $this->ui($rest);
        } else if ($rest->getRequest()->getExtension() == "csv") {
            return $this->csv($rest);
        } else {
            return $this->json($rest);
        }
    }

    private function json($rest)
    {
        if ($this->success) {
            $data = $rest->getParameter("data");
        } else {
            $data = [];
        }

        $response = new \StdClass;
        $response->success = $this->success;
        $response->message = $this->message;
        $response->total = count($data);
        $response->data = $data;

        $rest->getResponse()->setResponse(json_encode($response));
        return $rest;
    }

    private function csv($rest)
    {
        if (!$this->success) {
            $rest->getResponse()->setResponse($message);
            return $rest;
        }

        $data = $rest->getParameter("data");
        if (count($data) >= 1) {
            $columns = [];
            foreach ($data[0] as $k => $value) {
                $columns[] = '"' . $k . '"';
            }
            $rest->getResponse()->addResponse(implode(",", $columns) . "\n");
            foreach ($data as $record) {
                $values = [];
                foreach ($record as $value) {
                    $values[] = '"' . $value . '"';
                }
                $rest->getResponse()->addResponse(implode(",", $values) . "\n");
            }
        }
        return $rest;
    }
}