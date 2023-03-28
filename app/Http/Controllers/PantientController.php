<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePantientRequest;
use App\Models\Address;
use App\Models\Pantient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\UploadedFile;

class PantientController extends Controller
{


    /**
     * list all pantients
     * @return [type]
     */
    public function index()
    {
        $products = Pantient::all()->toArray();
        return array_reverse($products);
    }

    /**
     * create new register for pacients
     * @param Request $request
     * 
     * @return mixed
     */
    public function store(StorePantientRequest $request): mixed
    {
        $validatedData = $request->validated();
        //validate cns
        if (!$this->validateCns($validatedData['cns']))
            return response()->json('cns invalido por favor preencher cns valido!');

        //validate cep
        if (!$this->validateCep($validatedData['cep']))
            return response()->json('cep invalido por favor preencher cep valido!');

        //Consult cep
        $address = $this->ConsultCep($request);
        if (!$address)
            return response()->json('api de consulta do cep fora do ar!');

        //validate photo
        $validatedData['photo'] = $request->file('photo')->store('image');

        //Convert date
        $date = date('Y-m-d H:i:s',strtotime($validatedData['birthday']));

        $pantient = new Pantient([
            'photo' => $validatedData['photo'],
            'name' => $validatedData['name'],
            'mon' => $validatedData['mon'],
            'birthday' => $date,
            'cpf' => $validatedData['cpf'],
            'cns' => $validatedData['cns'],
            'address_id' => $address->id,
        ]);

        $pantient->save();

        return response()->json('Pantient created!');
    }

    /**
     * 
     * @param mixed $id
     * 
     * @return [type]
     */
    public function show($id)
    {
        $pantient = Pantient::find($id);
        return response()->json($pantient);
    }

    /**
     * @param mixed $id
     * @param Request $request
     * 
     * @return [type]
     */
    public function update($id, Request $request)
    {
        $pantient = Pantient::find($id);
        $pantient->update($request->all()); 
        return response()->json('Pantient updated!');
    }

    /**
     * @param mixed $id
     * 
     * @return [type]
     */
    public function destroy($id)
    {
        $pantient = Pantient::find($id);
        $pantient->delete();
        return response()->json('Pantient deleted!');
    }

    /**
     * @param Mixed $photo
     * 
     * @return mixed
     */
    public function UploadFile(Request $request): mixed
    {
       
        $data = Image::create($result);
    }

    /**
     * @param String $cep
     * 
     * @return [type]
     */
    function validateCep(String $cep)
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
     * @param Request $request
     * 
     * @return mixed
     */
    function ConsultCep(Request $request): mixed
    {
        $cep = str_replace('-', '', $request['cep']);
        $redis = Redis::get('redis:cep:' . $cep);
        if (!$redis) {
            $count = Address::where('cep', $cep)->count();

            if ($count > 0) {
                $address = Address::where('cep', $cep)->first();
                Redis::set('redis:cep:' . $cep,  json_encode($address->toArray()));
                return $address->toArray();
            }

            $json = $this->getCep($request);
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
     * @param Request $request
     * 
     * @return mixed
     */
    public function getCep(Request $request): mixed
    {
        $request->validate([
            'cep' => 'required',
        ]);

        $result = $request->route('cep') ?? $request->input('cep');
        if (!$this->validateCep($result))
            return response()->json('informe um cep valido');

        $cep = str_replace('-', '', $result);

        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://viacep.com.br/ws/" . $cep . "/json/");
        $result = $response->getBody();
        $json = json_decode($result);

        return $json;
    }


    /**
     * @param mixed $cns
     * 
     * @return bool
     */
    function validateCns($cns): bool
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
     * @param string $cns
     * 
     * @return bool
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
     * @param string $cns
     * 
     * @return bool
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
