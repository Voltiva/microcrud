<?php

namespace Microcrud\Abstracts;



class CrudService extends Service
{
    public Model $model;
    protected array $data;
    protected $private_key_name = 'id';

    protected $is_job = false;

    protected $query = null;
}
