<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TagModel;

use \Validator;
use \Exception;
use \DB;

class TagController extends Controller
{
    public function saveTag(Request $request) {
        try {
            $values = $request->input();

            // VALIDACIONES DE CAMPOS OBLIGATORIOS POR LARAVEL
            $rules = [
                'tagName' => 'required',
            ];
            $input = [
                'tagName'   => $values['tagName'],
            ];
            $erros = [
                'tagName.required' => 'Nombre de etiqueta abligatorio',
            ];

            $validate = Validator::make($input, $rules, $erros);
            if($validate->fails()){
                $messages = $validate->messages()->all();
                return ['error' => 1, 'info' => $messages];
            }

            // SI TRAE idTag MODIFICA SINO CREA NUEVO
            if( !$values['idTag'] ){
                $tag = new TagModel();
            } else {
                $tag = TagModel::findOrFail($values['idTag']);
            }
            $tag->name = $values['tagName'];

            if(!$tag->save()){
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible Guardar etiqueta.'];
            }

            return ['error' => 0,'info' => 'Etiqueta guardada correctamente'];

        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function getTags(Request $request) {
        try {
            $values = $request->input();

            $tag = new TagModel();
            $TA  = $tag->getTable();

            $tags = $tag->select("$TA.id", "$TA.name")
            ->where(function($query) use ($values, $TA) {
                if(!empty($values['inputSearch'])) {
                    $query->orWhere("$TA.name", 'like', "%".$values['inputSearch']."%");
                }
            })
            ->orderBy("$TA.name")
            ->get();

            $response = [
                'tags'=> $tags
            ];

            return ['error' => 0, 'info' => $response];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function deleteTag(Request $request) {
        try {
            $tag = TagModel::findOrFail($request->idTag);
            if(!$tag->delete()){
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible eliminar etiqueta.'];
            }

            return ['error' => 0, 'info' => 'Etiqueta borrada correctamente'];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }
}
