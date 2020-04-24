<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as GuzzleHttp;

final class NotesTest extends TestCase
{
    public function testInit()
    {
        global $argv, $argc;
        $this->assertGreaterThan(2, $argc, 'No environment name passed');
        file_put_contents(__DIR__ . "/../data/url", $argv[2]);
        // Reset database projects
        /*
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url")
        ]);
        for ($i = 1; $i < 100; $i++) {
            try {
                $client->delete("/api/notes/{$i}");
            } catch (\GuzzleHttp\Exception\ClientException $e) {
            }
        }
        */
    }

    public function testEmptyNotes()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url")
        ]);
        $response = $client->get('/api/notes');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody());
        $this->assertObjectHasAttribute('notes', $data);
        $this->assertEmpty($data->notes);
        $this->assertObjectHasAttribute('error', $data);
        $this->assertEmpty($data->error);
    }

    public function testCanBeCreated()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url")
        ]);
        $request = [
            'content' => 'lorem ipsum',
        ];
        $response = $client->post('/api/notes', ['form_params' => $request]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody());
        $this->assertIsObject($data);
        $this->assertObjectHasAttribute('note', $data);
        $this->assertObjectHasAttribute('created_at', $data->note);
        $this->assertObjectHasAttribute('updated_at', $data->note);
        $this->assertObjectHasAttribute('content', $data->note);
        $this->assertEquals($request['content'], $data->note->content);
        $this->assertObjectHasAttribute('id', $data->note);
        $this->assertObjectHasAttribute('error', $data);
        $this->assertEmpty($data->error);
        // Save note id created for next tests
        file_put_contents(__DIR__ . "/../data/noteId", $data->note->id);
    }

    public function testWrongParameterForCreation()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
        ]);
        try {
            $request = [
                'body' => 'lorem ipsum',
            ];
            $client->post('/api/notes', ['form_params' => $request]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(422, $e->getCode());
        }
    }

    public function testGetNotes()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url")
        ]);
        $response = $client->get('/api/notes');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody());
        $this->assertIsObject($data);
        $this->assertObjectHasAttribute('notes', $data);
        $this->assertIsArray($data->notes);
        $this->assertObjectHasAttribute('created_at', $data->notes[0]);
        $this->assertObjectHasAttribute('updated_at', $data->notes[0]);
        $this->assertObjectHasAttribute('content', $data->notes[0]);
        $this->assertObjectHasAttribute('id', $data->notes[0]);
        $this->assertObjectHasAttribute('error', $data);
        $this->assertEmpty($data->error);
    }

    public function testNoteDoesntExists()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
            "headers" => [
                'Accept' => 'application/json',
            ]
        ]);
        try {
            $client->get('/api/notes/3456789');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(404, $e->getCode());
            $data = json_decode($e->getResponse()->getBody()->getContents());
            $this->assertIsObject($data);
            $this->assertObjectHasAttribute('error', $data);
            $this->assertEquals($data->error, "Cet identifiant est inconnu");
        }
    }

    public function testCanBeUpdate()
    {
        $noteId = file_get_contents(__DIR__ . "/../data/noteId");
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
        ]);
        $request = [
            'content' => 'lorem ipsum 2',
        ];
        $response = $client->put("/api/notes/{$noteId}", ['form_params' => $request]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody());
        $this->assertIsObject($data);
        $this->assertObjectHasAttribute('note', $data);
        $this->assertIsObject($data->note);
        $this->assertObjectHasAttribute('created_at', $data->note);
        $this->assertObjectHasAttribute('updated_at', $data->note);
        $this->assertObjectHasAttribute('content', $data->note);
        $this->assertEquals($request['content'], $data->note->content);
        $this->assertObjectHasAttribute('id', $data->note);
        $this->assertObjectHasAttribute('error', $data);
        $this->assertEmpty($data->error);
    }

    public function testWrongParameterForUpdate()
    {
        $noteId = file_get_contents(__DIR__ . "/../data/noteId");
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
        ]);
        $request = [
            'body' => 'lorem ipsum',
        ];
        try {
            $client->put("/api/notes/{$noteId}", ['form_params' => $request]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(422, $e->getCode());
        }
    }

    public function testNoteDoesntExistsForUpdate()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
            "headers" => [
                'Accept' => 'application/json',
            ]
        ]);
        $request = [
            'content' => 'lorem ipsum',
        ];
        try {
            $client->put("/api/notes/123456789", ['form_params' => $request]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(404, $e->getCode());
            $data = json_decode($e->getResponse()->getBody()->getContents());
            $this->assertIsObject($data);
            $this->assertObjectHasAttribute('error', $data);
            $this->assertEquals($data->error, "Cet identifiant est inconnu");
        }
    }

    public function testCanBeDeleted()
    {
        $noteId = file_get_contents(__DIR__ . "/../data/noteId");
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
        ]);
        $request = [
            'content' => 'lorem ipsum',
        ];
        $response = $client->delete("/api/notes/{$noteId}", ['form_params' => $request]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents());
        $this->assertIsObject($data);
        $this->assertObjectHasAttribute('error', $data);
        $this->assertEmpty($data->error);
    }

    public function testNoteDoesntExistsForDelete()
    {
        $client = new GuzzleHttp([
            'base_uri' => file_get_contents(__DIR__ . "/../data/url"),
            "headers" => [
                'Accept' => 'application/json',
            ]
        ]);
        try {
            $client->delete('/api/notes/3456789');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(404, $e->getCode());
            $data = json_decode($e->getResponse()->getBody()->getContents());
            $this->assertIsObject($data);
            $this->assertIsObject($data);
            $this->assertObjectHasAttribute('error', $data);
            $this->assertEquals($data->error, "Cet identifiant est inconnu");
        }
    }
}
