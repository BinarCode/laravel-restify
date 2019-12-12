<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserLoggedIn
{
    use SerializesModels;

    /**
     * @var Model
     */
    public $authenticated;

    /**
     * @var Request
     */
    public $request;

    /**
     * @param Model $authenticated
     * @param RequestStack $request
     */
    public function __construct(Model $authenticated, RequestStack $request)
    {
        $this->authenticated = $authenticated;
        $this->request = $request->getCurrentRequest();
    }
}
