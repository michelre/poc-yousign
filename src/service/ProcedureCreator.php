<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ProcedureCreator
{

    private $guzzleClient;
    private $headers;

    /**
     * ProcedureCreator constructor.
     */
    public function __construct()
    {
        $this->guzzleClient = new Client();
        $env = parse_ini_file(__DIR__ . '/../../env');
        $this->headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $env['API_KEY']];
    }

    public function create()
    {
        $users = ['remi.michel38@gmail.com', 'jlmichel4@gmail.com'];
        $userDocs = [
            'remi.michel38@gmail.com' => [
                'firstname' => 'Anthony',
                'lastname' => 'Cutrone',
                'email' => 'remi.michel38@gmail.com',
                //'phone' => '+33683356727',
                'phone' => '+33671209375',
                'docs' => [
                    'mandat-de-recherche-de-capitaux.pdf' => [['position' => '260,670,364,702', 'page' => 5], ['position' => '261,101,376,140', 'page' => 8]],
                    'notice-entree-en-relation.pdf' => [['page' => 3, 'position' => '107,230,231,264', 'name' => 'Notice entrée en relation']]
                ]
            ],
            'jlmichel4@gmail.com' => [
                'firstname' => 'Jean',
                'lastname' => 'Port',
                'email' => 'jlmichel4@gmail.com',
                'phone' => '+33671209375',
                'docs' => [
                    'mandat-de-recherche-de-capitaux.pdf' => [['position' => '270,658,374,690', 'page' => 4]],
                    'notice-entree-en-relation.pdf' => [['page' => 3, 'position' => '374,230,499,264']]
                ]
            ]
        ];
        $docs = ['mandat-de-recherche-de-capitaux.pdf', 'notice-entree-en-relation.pdf'];

        $procedure = json_decode($this->guzzleClient->post(
            'https://staging-api.yousign.com/procedures',
            ['headers' => $this->headers, 'json' => ['name' => 'Procédure', 'description' => 'Test procédure', 'start' => false]]
        )->getBody()->getContents(), true);

        foreach ($users as $user) {
            $member = json_decode($this->guzzleClient->post(
                'https://staging-api.yousign.com/members',
                ['headers' => $this->headers, 'json' =>
                    [
                        'firstname' => $userDocs[$user]['firstname'],
                        'lastname' => $userDocs[$user]['lastname'],
                        'email' => $userDocs[$user]['email'],
                        'phone' => $userDocs[$user]['phone'],
                        'procedure' => $procedure['id'],
                    ]
                ]
            )->getBody()->getContents(), true);
            $userDocs[$user]['memberId'] = $member['id'];
        }

        // Ajout des fichiers à signer
        $resFiles = array_map(function ($doc) use ($userDocs, $procedure, $users) {
            $file = base64_encode(file_get_contents(__DIR__ . '/../../assets/' . $doc));
            $resFile = json_decode($this->guzzleClient->post(
                'https://staging-api.yousign.com/files',
                ['headers' => $this->headers, 'json' => ['name' => $doc, 'content' => $file, 'procedure' => $procedure['id']]]
            )->getBody()->getContents(), true);
            foreach ($users as $user) {
                foreach ($userDocs[$user]['docs'][$doc] as $v) {
                    $this->guzzleClient->post(
                        'https://staging-api.yousign.com/file_objects',
                        ['headers' => $this->headers, 'json' =>
                            [
                                'file' => $resFile['id'],
                                'page' => $v['page'],
                                'position' => $v['position'],
                                'member' => $userDocs[$user]['memberId']
                            ]
                        ]
                    );
                }
            }
            return $resFile;
        }, $docs);

        $this->guzzleClient->put(
            'https://staging-api.yousign.com' . $procedure['id'],
            ['headers' => $this->headers, 'json' =>
                [
                    "config" => [
                        "email" => [
                            "member.started" => [
                                [
                                    "subject" => "Vous avez une nouvelle procédure",
                                    "message" => "Hello <tag data-tag-type=\"string\" data-tag-name=\"recipient.firstname\"></tag> <tag data-tag-type=\"string\" data-tag-name=\"recipient.lastname\"></tag> ! <br><br> You have ben added to a procedure, please access it here : <tag data-tag-type=\"button\" data-tag-name=\"url\" data-tag-title=\"Access to documents\">Access to documents</tag>",
                                    "to" => ['@member']
                                ]
                            ],
                            "procedure.started" => [
                                [
                                    "subject" => "Création d'une nouvelle procédure",
                                    "message" => "Blablabla",
                                    "to" => ['remi.michel38@gmail.com']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );


        return [$resFiles, $userDocs];
    }

    public function createSignatureUi()
    {
        try {
            $res = $this->guzzleClient->post(
                'https://staging-api.yousign.com/signature_uis',
                ['headers' => $this->headers,
                    'json' => ['name' => 'Redirection 1', 'redirectSuccess' => ["url" => "/procedure?signed=true"]]]
            );
            return json_decode($res->getBody()->getContents(), true);
        } catch (ClientException $e) {
            var_dump(json_decode($e->getResponse()->getBody()->getContents(), true));
        }
    }

    public function downloadFiles($files)
    {
        $arr = [];
        foreach ($files as $file) {
            $d = $this->guzzleClient->get(
                'https://staging-api.yousign.com' . $file['id'] . '/download',
                ['headers' => $this->headers]
            )->getBody()->getContents();
            file_put_contents(__DIR__ . '/../../docs/' . $file['name'], base64_decode($d));
            array_push($arr, __DIR__ . '/../../docs/' . $file['name']);
        }
        return $arr;
    }

}
