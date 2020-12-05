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
			$values['category'] = json_decode($values['category'], TRUE);
			$values['status'] 	= json_decode($values['status'], TRUE);
			$values['tags'] 	= json_decode($values['tags'], TRUE);

            // VALIDACIONES DE CAMPOS OBLIGATORIOS POR LARAVEL
            $rules = [
                'petName'  => 'required',
                'category' => 'required',
                // 'photo'    => 'required|file',
            ];
            $input = [
                'petName'  => $values['petName'],
                'category' => $values['category'],
                // 'photo'    => $values['photo'],
            ];
            $erros = [
                'petName.required'  => ' Nombre de mascota abligatorio',
                'category.required' => ' Categoría es abligatorio',
                // 'photo.required'    => ' Foto es abligatoria',
            ];

            $validate = Validator::make($input, $rules, $erros);
            if($validate->fails()){
                $messages = $validate->messages()->all();
                return ['error' => 1, 'info' => $messages];
            }


            ### Manejo datos foto enviada y validaccón ###
            if( $_FILES ){
                $tipoArchivo   = $_FILES['photo']['type'];
                $nombreArchivo = $_FILES['photo']['name'];
                $tamanoArchivo = $_FILES['photo']['size'];
                $imagenCargada = fopen($_FILES['photo']['tmp_name'],'r');
                $binarios      = fread($imagenCargada, $tamanoArchivo);
                $extension = explode('.', $_FILES['photo']['name']);
                $extension = end( $extension );

                if ( $extension != 'jpg' && $extension != 'jpeg' && $extension != 'png' && $extension != 'gif' && $extension != 'bmp' ) {
                    return ['error' => 1, 'info' => 'Formato de Archivo Incorrecto'];
                }
            }

            // SI TRAE idPet MODIFICA SINO CREA NUEVO
            if( !$values['idPet'] ){
                $pet = new PetModels();
            } else {
                $pet = PetModels::findOrFail($values['idPet']);
            }
            $pet->name 		= $values['petName'];
            $pet->category  = ($values['category']['id'] ? $values['category']['id'] : null );
            $pet->status 	= (isset($values['status']['id']) ? $values['status']['id'] : null);

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

            	if( $_FILES ){
	            	## Si no existe directorio lo crea ##
		    		if ( !file_exists("photos/") ) {
		                mkdir("photos/", 0777, true);
		            }
		            ## Mueve archivo
		            if ( !move_uploaded_file($_FILES["photo"]["tmp_name"], "photos/". $pet->id . "_" . date("His") . "." .$extension ) ) {
		                return ['error' => 1, 'info' => 'Error copiando Imagen'];
		            }
		            $ruta = "/photos/". $pet->id . "_" . date("His") .".".$extension;
		            PetModels::where('id', $pet['id'])->update(['photourls' => $ruta]);
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
            ->join("$CA", "$PE.category", "$CA.id")
            ->leftJoin("$ST", "$PE.status", "$ST.id")
            ->where(function($query) use ($values, $PE) {
                if(!empty($values['inputSearch'])) {
                    $query->orWhere("$PE.name", 'like', "%".$values['inputSearch']."%");
                    $query->orWhere("$PE.id", $values['inputSearch']);
                }
                if(!empty($values['searchStatus'])) {
                    $query->orWhere("$PE.status", $values['searchStatus']);
                }
            })
            ->orderBy("$PE.id")
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
