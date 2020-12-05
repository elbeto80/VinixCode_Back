<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PetModels;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Models\StatusModel;
use App\Models\PetTags;

use \Validator;
use \Exception;
use \DB;

class PetController extends Controller
{
    public function paramsPets(Request $request) {
        try {
            $params = [];

            $category = new CategoryModel();
            $tag      = new TagModel();
            $statu    = new StatusModel();
            $CA 	  = $category->getTable();
            $TA 	  = $tag->getTable();
            $ST 	  = $statu->getTable();

            $categories = $category->select("$CA.id","$CA.name")
            ->orderBy("$CA.name")
            ->get();
            $params['categories'] = $categories;

            $tags = $tag->select("$TA.id","$TA.name")
            ->orderBy("$TA.name")
            ->get();
            $params['tags'] = $tags;

            $status = $statu->select("$ST.id","$ST.name")
            ->orderBy("$ST.name")
            ->get();
            $params['status'] = $status;

            return ['error' => 0,'info'=> $params];

        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function savePet(Request $request) {
        try {
            $values = $request->input();

            // VALIDACIONES DE CAMPOS OBLIGATORIOS POR LARAVEL
            $rules = [
                'petName' => 'required',
                'photo'   => 'required',
            ];
            $input = [
                'petName'   => $values['petName'],
                'photo'     => $values['photo'],
            ];
            $erros = [
                'petName.required' => 'Nombre de mascota abligatorio',
                'photo.required'   => 'Foto es abligatoria',
            ];

            $validate = Validator::make($input, $rules, $erros);
            if($validate->fails()){
                $messages = $validate->messages()->all();
                return ['error' => 1, 'info' => $messages];
            }

            // SI TRAE idPet MODIFICA SINO CREA NUEVO
            if( !$values['idPet'] ){
                $pet = new PetModels();
            } else {
                $pet = PetModels::findOrFail($values['idPet']);
            }
            $pet->name 		= $values['petName'];
            $pet->category  = $values['category']['id'];
            $pet->photourls = $values['photo'];
            $pet->status 	= $values['status']['id'];

            if($pet->save()){
            	if($values['idPet']) {
            		PetTags::where('pet', $pet['id'])->where("active", "S")->update(['active' => 'N']);
            	}

            	foreach ($values['tags'] as $data) {
            		$tag = new PetTags();
            		$tag->pet = $pet['id'];
            		$tag->tag = $data['id'];
            		if(!$tag->save()){
						DB::rollback();
                		return ['error' => 1, 'info' => 'Error guardando etiqueta ' . $data['name']];
            		}
            	}
            } else {
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible Guardar mascota.'];
            }

            return ['error' => 0,'info' => 'Mascota guardada correctamente'];

        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function getPets(Request $request) {
        try {
            $values = $request->input();

            $pet 		= new PetModels();
            $status 	= new StatusModel();
            $category 	= new CategoryModel();
            $tag 		= new TagModel();
            $PE 	 	= $pet->getTable();
            $ST 		= $status->getTable();
            $CA 		= $category->getTable();
            $TA 		= $tag->getTable();

            $pets = $pet->select("$PE.id", "$PE.name", "$PE.category", "$PE.photourls", "$PE.status", "$ST.name as statusName", "$CA.name as categoryName")
            ->join("$ST", "$PE.status", "$ST.id")
            ->join("$CA", "$PE.category", "$CA.id")
            ->where(function($query) use ($values, $PE) {
                if(!empty($values['inputSearch'])) {
                    $query->orWhere("$PE.name", 'like', "%".$values['inputSearch']."%");
                }
            })
            ->orderBy("$PE.name")
            ->get();

            foreach ($pets as $index => $data) {
            	$tags = PetTags::where('pet', $data['id'])->where("active", 'S')->select("tag","name")->join("$TA", "tag", "$TA.id")->get()->toArray();
            	$pets[$index]['tags'] = $tags;
            }

            $response = [
                'pets'=> $pets
            ];

            return ['error' => 0, 'info' => $response];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function deletePet(Request $request) {
        try {
            $pet = PetModels::findOrFail($request->idPet);
            if(!$pet->delete()){
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible eliminar mascota.'];
            }

            return ['error' => 0, 'info' => 'Mascota borrada correctamente'];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }
}
