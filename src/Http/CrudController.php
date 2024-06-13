<?php

namespace Microcrud\Http;

use Illuminate\Http\Request;
use Microcrud\Interfaces\CrudBaseController;
use Microcrud\Abstracts\Http\ApiBaseController;
use Microcrud\Abstracts\Exceptions\CreateException;
use Microcrud\Abstracts\Exceptions\UpdateException;
use Microcrud\Abstracts\Exceptions\NotFoundException;
use Microcrud\Abstracts\Exceptions\ValidationException;

abstract class CrudController extends ApiBaseController implements CrudBaseController
{
    public function __construct($model, $service = null, $resource = null)
    {
        parent::__construct($model, $service, $resource);
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->indexRules());
            $itemsQuery = $this->service
                ->setData($data)
                ->beforeIndex()
                ->getQuery();
            if(array_key_exists('trashed_status', $data) && $this->service->is_soft_delete()){
                switch ($data['trashed_status']) {
                    case -1:
                        $itemsQuery = $itemsQuery->onlyTrashed();
                        break;
                    case 1:
                        $itemsQuery = $itemsQuery->withTrashed();
                        break;
                    default:
                        break;
                }
            }
            $this->service->setQuery($itemsQuery);
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        if($this->service->getIsPaginated()){
            return $this->paginateQuery();
        }else{
            return $this->getResource();
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->showRules());
            $item = $this->service
                ->setData($data)
                ->beforeShow()
                ->setById()
                ->get();
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (NotFoundException $th) {
            return $this->errorNotFound($th->getMessage(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        return $this->singleItem($this->service->getItemResource(), $item);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function create(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->createRules());
            $item = $this->service
                ->setData($data)
                ->create()
                ->get();
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (CreateException $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        return $this->created($this->service->getItemResource(), $item);
    }

    /**
     * Update resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function update(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->updateRules());
            $item = $this->service
                ->setData($data)
                ->setById()
                ->update()
                ->get();
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (UpdateException $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        } catch (NotFoundException $th) {
            return $this->errorNotFound($th->getMessage(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        return $this->accepted($this->service->getItemResource(), $item);
    }

    /**
     * Delete resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function delete(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->deleteRules());
            if($request->is_force_destroy){
                $this->service
                    ->setQuery($this->service->getQuery()->withTrashed());
            }
            $this->service
                ->setData($data)
                ->setById()
                ->delete();
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (NotFoundException $th) {
            return $this->errorNotFound($th->getMessage(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        return $this->noContent();
    }

    /**
     * Delete resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function restore(Request $request)
    {
        try {
            $data = $this->service->globalValidation($request->all(), $this->service->restoreRules());
            $item = $this->service
                ->setQuery($this->service->getQuery()->withTrashed())
                ->setData($data)
                ->setById()
                ->restore()
                ->get();
        } catch (ValidationException $th) {
            return $this->error($th->getMessage(), $th->getCode(), $th);
        } catch (NotFoundException $th) {
            return $this->errorNotFound($th->getMessage(), $th);
        } catch (\Exception $th) {
            return $this->errorBadRequest($th->getMessage(), $th);
        }
        return $this->accepted($this->service->getItemResource(), $item);
    }
}
