<?php

namespace App\Router;

use App\Service\ProcedureCreator;
use Klein\Klein;
use Phar;
use PharData;
use ZipArchive;

class Router
{
    private $klein;
    private $procedureCreator;

    public function __construct()
    {
        $this->klein = new Klein();
        $this->procedureCreator = new ProcedureCreator();
    }

    public function run()
    {
        $this->klein->respond('GET', '/', function () {
            require_once __DIR__ . '/../view/procedure-form.php';
        });

        $this->klein->respond('POST', '/procedure', function () {
            $resp = $this->procedureCreator->create();
            //$signatureUi = $this->procedureCreator->createSignatureUi();
            header('location: /procedure?members=' . json_encode($resp));
            die();
        });

        $this->klein->respond('GET', '/procedure', function ($request) {
            [$files, $members] = json_decode($_GET['members'], true);
            require_once __DIR__ . '/../view/procedure.php';
        });

        $this->klein->respond('GET', '/files', function ($request, $response) {
            $files = json_decode($_GET['files'], true);
            $this->procedureCreator->downloadFiles($files);
            $zip = new ZipArchive();
            $zip->open(__DIR__ . '/../../docs/docs.zip', ZipArchive::CREATE);

            // ADD FILES TO archive.tar FILE
            foreach ($files as $file) {
                $zip->addFile(__DIR__ . '/../../docs/' . $file['name'], $file['name']);
            }

            $zip->close();

            $response->file(__DIR__ . '/../../docs/docs.zip');
        });

        $this->klein->dispatch();
    }

}
