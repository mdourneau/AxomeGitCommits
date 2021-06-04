<?php

namespace AxomeGitCommits;

class GitHub {

    private $username;

    private $repository;

    private $branch;

    private $client;

    public function __construct($username, $repository, $branch)
    {
        $this->username = $username;
        $this->repository = $repository;
        $this->branch = $branch;

        $this->client = new \Github\Client();
    }

    /**
     * Get list of all commits in repository
     */
    public function getAllCommits()
    {
        return $this->client->api('repo')->commits()->all($this->username, $this->repository, ['sha' => $this->branch]);
    }
}