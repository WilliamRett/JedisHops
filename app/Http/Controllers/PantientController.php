<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePantientRequest;
use App\Models\Address;
use App\Models\Pantient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

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
     * @param StorePantientRequest $request
     * 
     * @return mixed
     */
    public function store(StorePantientRequest $request): mixed
    {
        //validate cns
        if (!$this->validateCNS($request->input('cns')))
            return response()->json('cns invalido por favor preencher cns valido!');

        //validate cep
        if (!$this->validateCep($request->input('cep')))
            return response()->json('cep invalido por favor preencher cep valido!');

        $address_id = $this->ConsultCep($request->input('cep'));
        if (!$address_id)
            return response()->json('api de consulta do cep fora do ar!');

        //validate photo
        if (!$request->hasFile('photo') && !$request->file('photo')->isValid())
            return response()->json('formato de foto nao suportado, favor enviar outro formato!');

        $url = $this->UploadFile($request->file('photo'));

        if (!$url) {
            return response()->json('Falha ao fazer upload');
        }

        $pantient = new Pantient([
            'photo' => $url,
            'name' => $request->input('name'),
            'mon' => $request->input('mon'),
            'birthday' => $request->input('birthday'),
            'cpf' => $request->input('cpf'),
            'cns' => $request->input('cns'),
            'address_id' => $address_id,
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
    public function UploadFile(Mixed $photo): mixed
    {
        $name = uniqid(date('HisYmd'));
        $extension = $photo->image->extension();
        $nameFile = "{$name}.{$extension}";
        $upload = $photo->image->storeAs('galery', $nameFile);
        if (!$upload)
            return false;
        return $upload;
    }

    /**
     * @param String $cns
     * 
     * @return [type]
     */
    function validateCNS(String $cns)
    {
        $cns = preg_replace('/[^0-9]/', '', $cns);

        if (strlen($cns) != 15) {
            return false;
        }

        $bigint = 0;

        for ($i = 0; $i < 15; $i++) {
            $bigint += intval(substr($cns, $i, 1)) * (15 - $i);
        }

        $dv1 = 11 - ($bigint % 11);

        if ($dv1 == 11) {
            $dv1 = 0;
        }

        $bigint = 0;

        for ($i = 0; $i < 15; $i++) {
            $bigint += intval(substr($cns, $i, 1)) * (15 - $i + 1);
        }

        $bigint += $dv1 * 2;

        $dv2 = 11 - ($bigint % 11);

        if ($dv2 == 11) {
            $dv2 = 0;
        }

        return (intval(substr($cns, 0, 1)) == 1 || intval(substr($cns, 0, 1)) == 2)
            && intval(substr($cns, 10, 3)) != 0
            && intval(substr($cns, 11, 4)) != 0
            && intval(substr($cns, 15, 1)) == $dv1
            && intval(substr($cns, 16, 1)) == $dv2;
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
        $redis = Redis::get('redis-cep-' . $request->input('cep'));

        if (!$redis) {
            $count = Address::where('cep', $request->input('cep'))->count();

            if ($count > 0) {
                $address = Address::where('cep', $request->input('cep'))->first();
                Redis::set('redis-cep-' . $address->cep, $address);
                return $address->id;
            }

            $json = $this->getCep($request);
            $address = new Address([
                'address' => $json->logradouro,
                'neighborhood' =>  $json->bairro,
                'city' => $json->localidade,
                'state' => $json->uf,
                'cep' => $json->cep,
            ]);
            $address->save();
            Redis::set('redis-cep-' . $address->cep, $address);
            return $address->id;
        }

        return $redis->address->id;
    }


    /**
     * @param Request $request
     * 
     * @return mixed
     */
    public function getCep(Request $request):mixed
    {
        dd('entrou');
        $result = $request->route('cep') ?? $request->input('cep');
        if (!$this->validateCep($result))
            return response()->json('informe um cep valido');

        $cep = str_replace('-', '', $result);

        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://viacep.com.br/ws/" . $cep . "/json/");
        $result = $response->getBody();
        $json = json_decode($result);
        dd($json);
        
        return $json;
    }
}
