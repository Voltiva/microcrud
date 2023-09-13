<?php

namespace Microcrud\Abstracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Microcrud\Abstracts\Exceptions\NotFoundException;
use Microcrud\Abstracts\Exceptions\CreateException;
use Microcrud\Abstracts\Exceptions\UpdateException;
use Microcrud\Abstracts\Jobs\StoreJob;
use Microcrud\Abstracts\Jobs\UpdateJob;
use Microcrud\Interfaces\ServiceInterface;
use Illuminate\Support\Str;
use Microcrud\Abstracts\Exceptions\ValidationException;
use Microcrud\Requests\DestroyRequest;
use Microcrud\Requests\PaginationRequest;
use Microcrud\Requests\ShowRequest;
use Illuminate\Support\Facades\Validator;
use Microcrud\Responses\ClientItemResource;
use Microcrud\Responses\ItemResource;

abstract class Service implements ServiceInterface
{
    public $model;
    protected array $data = [];
    protected $private_key_name = 'id';

    protected $is_job = false;

    protected $query = null;
    protected $resource = null;
    protected $client_resource = null;
    /**
     * Class constructor.
     */
    public function __construct($model, $resource = null, $client_resource = null)
    {
        $this->model = $model;
        $this->resource = (isset($resource)) ? $resource : ItemResource::class;
        $this->client_resource = (isset($client_resource)) ? $client_resource : ClientItemResource::class;
    }

    public function setPrivateKeyName($private_key_name)
    {
        $this->private_key_name = $private_key_name;
        return $this;
    }

    /**
     * @throws NotFoundException
     */
    public function get()
    {
        if (!isset($this->model)) {
            throw new NotFoundException();
        }
        return $this->model;
    }

    public function getQuery($request = null)
    {
        return ($this->query) ? $this->query : $this->model::query();
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @throws NotFoundException
     */
    public function set($model)
    {
        if (isset($model)) {
            $this->model = $model;
        } else {
            throw new NotFoundException();
        }
        return $this;
    }

    /**
     * @throws NotFoundException
     */
    public function setById($data = [])
    {
        if (empty($data)) {
            $data = $this->getData();
        }
        if (array_key_exists($this->private_key_name, $data)) {
            ksort($data);
            $item_key = $this->getModelTableName() . ':' . serialize($data);
            $model = Cache::tags([$this->getModelTableName()])
                ->remember(
                    $item_key,
                    Carbon::now()->addDay(),
                    function () use ($data) {
                        return $this->withoutScopes()->getQuery()->where($this->private_key_name, $data[$this->private_key_name])->first();
                    }
                );
            if ($model) {
                $this->set($model);
            } else {
                throw new NotFoundException('Not found with id:' . $data[$this->private_key_name]);
            }
        } else {
            throw new NotFoundException('Request key not found with name:' . $this->private_key_name);
        }
        return $this;
    }

    /**
     * @throws NotFoundException
     */
    public function getData()
    {
        if (!isset($this->data)) {
            throw new NotFoundException();
        }
        return $this->data;
    }
    /**
     * @throws NotFoundException
     */
    public function setData(array $data)
    {
        if (isset($data)) {
            $this->data = $data;
        } else {
            throw new NotFoundException();
        }
        return $this;
    }
    public function beforeCreate()
    {
        $rules = $this->model->create_rules;

        return $this;
    }
    public function createJob($data)
    {
        $this->is_job = true;
        StoreJob::dispatchSync($data, $this);
        return $this;
    }
    /**
     * @throws CreateException
     */
    public function create($data = [])
    {
        DB::beginTransaction();
        try {
            if (!empty($data)) {
                $this->setData($data);
            }
            $this->beforeCreate();            
            $data = $this->getData();
            $keys = $this->getModelColumns();
            $filtered_data = array_intersect_key($data, array_flip($keys));
            $model = $this->model::create($filtered_data);
        } catch (\Exception $exception) {
            DB::rollBack();
            $message = "Cannot create. ERROR:{$exception->getMessage()}. TRACE: {$exception->getTraceAsString()}";
            if ($this->is_job) {
                Log::error($message);
            } else {
                throw new CreateException($message);
            }
        }
        DB::commit();
        $this->set($model);
        $this->afterCreate();
        return $this;
    }

    public function afterCreate()
    {
        Cache::tags($this->getModelTableName())->flush();
        return $this;
    }
    public function beforeUpdate()
    {
        return $this;
    }

    public function updateJob($data)
    {
        $this->is_job = true;
        UpdateJob::dispatchSync($data, $this);
        return $this;
    }
    /**
     * @throws UpdateException
     */
    public function update($data = [])
    {
        DB::beginTransaction();
        try {
            if (!empty($data)) {
                $this->setData($data);
            }
            $this->beforeUpdate();                        
            $data = $this->getData();
            $keys = $this->getModelColumns();
            $filtered_data = array_intersect_key($data, array_flip($keys));
            $this->get()->update($filtered_data);
        } catch (\Exception $exception) {
            DB::rollBack();
            $message = "Cannot update. ERROR:{$exception->getMessage()}. TRACE: {$exception->getTraceAsString()}";
            if ($this->is_job) {
                Log::error($message);
            } else {
                throw new UpdateException($message);
            }
        }
        DB::commit();
        $this->afterUpdate();
        return $this;
    }
    public function afterUpdate()
    {
        Cache::tags($this->getModelTableName())->flush();
        return $this;
    }

    public function createOrUpdate($data)
    {
        if ($model = $this->model::where($this->private_key_name, $data[$this->private_key_name])->first()) {
            $this->set($model)->update($data);
        } else {
            $this->create($data);
        }
        return $this;
    }
    public function beforeDelete()
    {
        return $this;
    }

    public function delete()
    {
        $this->beforeDelete();
        $data = $this->getData();
        if(array_key_exists('is_force_destroy', $data) && $data['is_force_destroy']){
            $this->get()->forceDelete();
        }else{
            $this->get()->delete();
        }
        $this->afterDelete();
        return $this;
    }
    public function afterDelete()
    {
        Cache::tags($this->getModelTableName())->flush();
        return $this;
    }

    public function withoutScopes(array $scopes = [])
    {
        $new_query = $this->getQuery();
        if (count($scopes) > 0) {
            foreach ($scopes as $scope) {
                $new_query = $new_query->withoutGlobalScope($scope);
            }
        } else {
            $new_query->withoutGlobalScopes();
        }
        $this->setQuery($new_query);
        return $this;
    }

    public function getModelColumns($model = null)
    {
        if (!$model) {
            $model = $this->model;
        }
        $keys = $model->getConnection()->getSchemaBuilder()->getColumnListing($this->getModelTableName($model));
        return $keys;
    }

    public function indexRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $model_rules = (new PaginationRequest)->rules();
            return array_merge($model_rules, $rules);
        }
    }


    public function showRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $model_rules = (new ShowRequest)->rules();
            return array_merge($model_rules, $rules);
        }
    }

    public function findAllRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $model_rules = (new PaginationRequest)->rules();
            return array_merge($model_rules, $rules);
        }
    }


    public function findOneRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $model_rules = (new ShowRequest)->rules();
            return array_merge($model_rules, $rules);
        }
    }

    public function destroyRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $model_rules = (new DestroyRequest)->rules();
            return array_merge($model_rules, $rules);
        }
    }

    public function storeRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $keys = $this->getModelColumns();
            $model_rules = [];
            $required_fields = [];
            $exceptional_fields = ['id', 'updated_at', 'created_at', 'deleted_at'];
            foreach ($keys as $key) {
                // $type = Schema::getColumnType($table, $key);
                // $model_rules[$key]='required|'.$type;
                if (in_array($key, $exceptional_fields)) {
                    //skip
                    continue;
                }
                $rule = 'required';
                if (in_array($key, $required_fields)) {
                    $rule = 'required';
                }
                $rule = $this->getRelationRule($key, $rule);
                $model_rules[$key] = $rule;
            }
            return array_merge($model_rules, $rules);
        }
    }

    public function updateRules($rules = [], $replace = false)
    {
        if ($replace) {
            return $rules;
        } else {
            $keys = $this->getModelColumns();
            $model_rules = [];
            $required_fields = ['id'];
            $exceptional_fields = ['updated_at', 'created_at', 'deleted_at'];
            foreach ($keys as $key) {
                // $type = Schema::getColumnType($table, $key);
                // $model_rules[$key]='required|'.$type;
                if (in_array($key, $exceptional_fields)) {
                    //skip
                    continue;
                }
                $rule = 'sometimes';
                if (in_array($key, $required_fields)) {
                    $rule = 'required';
                }
                $rule = $this->getRelationRule($key, $rule);
                $model_rules[$key] = $rule;
            }
            return array_merge($model_rules, $rules);
        }
    }

    private function getRelationRule($key, $rule)
    {
        if (str_ends_with($key, '_id')) {
            $relation_table = Str::plural(str_replace('_id', '', $key));
            $relation = Str::camel(str_replace('_id', '', $key));
            if (
                method_exists($this->model, $relation)
                && $this->model->{$relation}() instanceof \Illuminate\Database\Eloquent\Relations\Relation
            ) {
                $relation_model = (new $this->model)->{$relation}()->getRelated();
                $tableName = $this->getModelTableName($relation_model);
                $schema = $relation_model->getConnectionName();
                $relation_keys = $this->getModelColumns($relation_model);
                if (in_array($key, $relation_keys)) {
                    $rule = $rule . "|exists:{$schema}.{$tableName},{$key}";
                } else {
                    $rule = $rule . "|exists:{$schema}.{$tableName},id";
                }
            } else if (Schema::hasTable($relation_table)) {
                $rule = $rule . "|exists:{$relation_table},id";
            }
        }
        return $rule;
    }

    /**
     * @throws ValidationException
     */
    public function globalValidation($data, $rules = [])
    {
        if (count($rules)) {
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator->errors()->first(), 422);
            }
            $data = $validator->validated();
        }
        return $data;
    }

    public function getItemResource()
    {
        return $this->resource;
    }

    public function getClientItemResource()
    {
        return $this->client_resource;
    }

    public function setItemResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    public function setClientItemResource($client_resource)
    {
        $this->client_resource = $client_resource;
        return $this;
    }

    public function getModelTableName($model = null)
    {
        if (!$model) {
            $model = $this->model;
        }
        return $model->getTable();
    }
}
