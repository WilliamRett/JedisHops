<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePantientRequest;
use App\Models\Address;
use App\Models\Pantient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PantientController extends Controller
{
    /**
     * list all pantients
     *
     * @return [type]
     *
     */
    public function index()
    {
        $products = Pantient::all()->toArray();
        return array_reverse($products);
    }


    /**
     * create new register for pacients
     *
     * @param StorePantientRequest $request
     *
     * @return mixed
     *
     */
    public function store(StorePantientRequest $request): JsonResponse
    {
        try {
            $pantient = new Pantient();
            $validatedData = $request->validated();
            $pantient = $this->registerValidate($validatedData, $pantient);
            $pantient->save();
        } catch (Exception $ex) {
            report($ex);
            return response()->json($ex->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            report($e);
            return response()->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return response()->json('Pantient created!', Response::HTTP_OK);
    }


    /**
     * retrieve a patient record
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     */
    public function show(int $id): JsonResponse
    {
        $pantient = Pantient::find($id);
        return response()->json($pantient, Response::HTTP_OK);
    }



    /**
     * [Description for update]
     *
     * @param int $id
     * @param request $request
     *
     * @return JsonResponse
     *
     */
    public function update(int $id, request $request): JsonResponse
    {
        $pantient = Pantient::with(['address'])->where('id',$id)->get();
        dd($pantient);
        $validatedData = $request->all();

        try {
            $pantient = $this->registerValidate($validatedData, $pantient);
            $pantient->update();
        } catch (Exception $ex) {
            report($ex);
            return response()->json($ex->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            report($e);
            return response()->json($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return response()->json('Pantient updated!', Response::HTTP_OK);
    }


    /**
     * [Description for destroy]
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $pantient = Pantient::find($id);
            $pantient->delete();
        } catch (\Throwable $e) {
            report($e);
            return response()->json($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        return response()->json('Pantient deleted!');
    }


    /**
     * [Description for registerValidate]
     *
     * @param Array $request
     * @param Pantient|null $pantient
     *
     * @return mixed
     *
     */
    public function registerValidate(array $request, Pantient $pantient = null): mixed
    {
        $model = $pantient ?? new Pantient();

        $name     = $request['name']  ?? $model->name;
        $photo    = $model->photo     ?? $request['photo'];
        $mon      = $request['mon']   ?? $model->mon;
        $cpf      = $request['cpf']   ?? $model->cpf;
        $cns      = $request['cns']   ?? $model->cns;
        $cep      = $request['cep']   ?? $model->with('address:id');
        $birthday = date('Y-m-d H:i:s', strtotime($request['birthday'])) ?? $model->birthday;



        if (!empty($request)) {
            //validate cns
            if (!$this->validateCns($request['cns']))
            throw ValidationException::withMessages(['msg' => 'cns invalido por favor preencher cns valido!', 'status' => Response::HTTP_UNPROCESSABLE_ENTITY]);

            //validate cep
            if (!$this->validateCep($request['cep']))
            throw ValidationException::withMessages(['msg' => 'cep invalido por favor preencher cep valido!', 'status' => Response::HTTP_UNPROCESSABLE_ENTITY]);

            //Consult cep
            $address = $this->ConsultCep($request['cep']);
            if (!$address)
            throw ValidationException::withMessages(['msg' => 'api de consulta do cep fora do ar!', 'status' => Response::HTTP_UNPROCESSABLE_ENTITY]);

            //validate photo
            if (isset($request['photo'])) {
                $request['photo'] = Storage::disk('img')->put($request['photo'], 'Contents');
            }
        }

        $model->name = $name;
        $model->photo = $photo;
        $model->mon = $mon;
        $model->cpf = $cpf;
        $model->cns = $cns;
        $model->birthday = $birthday;
        $model->address_id = $address->id;

        return $model;
    }




    /**
     * zip code valid
     *
     * @param String $cep
     *
     * @return bool
     *
     */
    function validateCep(String $cep): bool
    {
        // Remove caracteres não numéricos do CEP
        $cep = preg_replace('/[^0-9]/', '', $cep);

        // Verifica se o CEP tem 8 dígitos
        if (strlen($cep) != 8) {
            return false;
        }
        return true;
    }


    /**
     * [Description for ConsultCep]
     *
     * @param string $request
     *
     * @return mixed
     *
     */
    function ConsultCep(string $cep): mixed
    {
        $cep = str_replace('-', '', $cep);
        $redis = Redis::get('redis:cep:' . $cep);
        if (!$redis) {
            $count = Address::where('cep', $cep)->count();
            if ($count > 0) {
                $address = Address::where('cep', $cep)->first();
                Redis::set('redis:cep:' . $cep,  json_encode($address->toArray()));
                return $address->toArray();
            }

            $json = $this->getCep($cep);
            $address = new Address([
                'address' => $json->logradouro,
                'neighborhood' =>  $json->bairro,
                'city' => $json->localidade,
                'state' => $json->uf,
                'cep' => $cep,
            ]);
            $address->save();

            Redis::set('redis:cep:' . $cep, json_encode($address->toArray()));
            return $address->toArray();
        }
        $result = json_decode($redis);
        return $result;
    }



    /**
     * [Description for getCep]
     *
     * @param Request $request
     *
     * @return mixed
     *
     */
    public function getCep(String $cep): mixed
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://viacep.com.br/ws/" . $cep . "/json/");
        $result = $response->getBody();
        $json = json_decode($result);
        return $json;
    }



    /**
     * [Description for validateCns]
     *
     * @param string $cns
     *
     * @return bool
     *
     */
    function validateCns(string $cns): bool
    {
        if (strlen(trim($cns)) != 15) {
            return false;
        }

        $initValue = substr($cns, 0, 1);

        if ($initValue <= 2 && $initValue >= 1) {
            return $this->cnsValidateForInitOneAndTwo($cns);
        }

        if ($initValue <= 9 && $initValue >= 7) {
            return $this->ValidateCnsProv($cns);
        }
    }


    /**
     * [Description for cnsValidateForInitOneAndTwo]
     *
     * @param string $cns
     *
     * @return bool
     *
     */
    function cnsValidateForInitOneAndTwo(string $cns): bool
    {
        $soma = 0;
        $resto = 0;
        $dv = 0;
        $pis = "";
        $resultado = "";
        $pis = substr($cns, 0, 11);

        for ($i = 0; $i < 11; $i++) {
            $soma += (int) $pis[$i] * (15 - $i);
        }

        $resto = $soma % 11;
        $dv = 11 - $resto;

        if ($dv == 11) {
            $dv = 0;
        }

        if ($dv == 10) {
            $soma = 0;
            for ($i = 0; $i < 11; $i++) {
                $soma += (int) $pis[$i] * (15 - $i);
            }
            $soma += 2;
            $resto = $soma % 11;
            $dv = 11 - $resto;
            $resultado = $pis . "001" . (string) $dv;
        } else {
            $resultado = $pis . "000" . (string) $dv;
        }

        if ($cns != $resultado) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * [Description for ValidateCnsProv]
     *
     * @param string $cns
     *
     * @return bool
     *
     */
    function ValidateCnsProv(string $cns): bool
    {
        if (strlen(trim($cns)) != 15) {
            return false;
        }

        $soma = 0;

        for ($i = 0; $i < 15; $i++) {
            $soma += (int)$cns[$i] * (15 - $i);
        }

        $resto = $soma % 11;

        if ($resto != 0) {
            return false;
        } else {
            return true;
        }
    }
}
