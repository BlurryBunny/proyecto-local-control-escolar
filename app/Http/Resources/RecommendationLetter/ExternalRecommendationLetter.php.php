<?php

namespace App\Http\Controllers\Resources\RecommendationLetter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRecommendationLetter;
use Illuminate\Http\{
    JsonResponse,
};
use App\Models\{
    Archive,
    ArchiveRequiredDocument,
    CustomParameter,
    MyRecommendationLetter,
    Parameter,
    User,
    RecommendationLetter,
    RequiredDocument,
    ScoreParameter,
};
use Barryvdh\DomPDF\Facade\Pdf;

class ExternalRecommendationLetter extends Controller
{
    public function __construct()
   {
       $this->middleware('guest');
   }
   
    /**
     * Agrega la carta de recomendacion 
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function addRecommendationLetter(StoreRecommendationLetter $request)
    {
        # Se busca expediente, para asignar nombre
        $archive = Archive::find($request->archive_id);

        # Cartas de recomendacion en expediente
        $num_recommendation_letter_count = $archive->archiveRequiredDocuments()
            ->whereNotNull('location')
            ->whereIsRecommendationLetter()
            ->count();

            #Se verifica el numero de cartas de recomendacion ya enviadas por archivo de solicitante
        if ($num_recommendation_letter_count > 2) {
            return new JsonResponse('Cartas enviadas, no se permiten mas', 200);
        }

        #Ids para relacion a archive required document table
        $required_document_id  = ($num_recommendation_letter_count < 1) ? 19 : 20; //Maximo de dos cartas, por lo tanto sera solo (0,1)

        # Crear archivo PDF
        //Se guarda en una variable local 
        $recommendation_letter_pdf = PDF::loadView('pdf.recommendation-letter', $request)
            ->setOptions([
                'defaultPaperSize' => 'a4',
                'isJavaScriptEnabled' => true,
                'isFontSubsettingEnabled' =>  true,
                'dpi' => 120
            ]); //opciones para visualizar correctamente el pdf

        //Se guarda en STORAGE
        $path = 'archives/' . $request->archive_id . '/recommendation-letter/' . $request->recommendation_letter_id . '_answerBy' . $archive->email_evaluator . '_#' . $num_recommendation_letter_count .  '.pdf';
        $recommendation_letter_pdf->save(storage_path($path));

        //Se guardan los datos en la tabla de recommendation_letter 
        $data_rl_table = $request->safe()->except('score_parameters', 'custom_parameters', 'recommendation_letter_id');
        MyRecommendationLetter::where('id', $request->recommendation_letter_id)->update($data_rl_table);

        $data_score_table = $request->safe()->only('score_parameters');
        //ciclo para crear y guardar los datos de score

        $data_custom_parameter =  $request->safe()->only('custom_parameters');
        //ciclo para crear y guardar parametros personalizadOS

        // #Creacion de modelos 

        // //Se crea carta de recomendacion (campos de texto y llaves foraneas)
        // $recommendation_letter  = MyRecommendationLetter::create($data_rl_table);
        // $res_rec = new JsonResponse($recommendation_letter);

        // foreach ($data_score_table as $data) {
        //     $res_param = new JsonResponse(ScoreParameter::create($data->validate([
        //         'rl_id' => ['required', 'integer'],
        //         'parameter_id' => ['required', 'integer'],
        //         'score' => ['required', 'string', 'max:255']
        //     ])));
        // }

        // foreach ($data_custom_parameter as $data) {
        //     $res_cust_param = new JsonResponse(CustomParameter::create($data->validate([
        //         'rl_id' => ['required', 'integer', 'max:255'],
        //         'text' => ['required', 'string', 'max:255'],
        //         'score' => ['required', 'string', 'max: 225']
        //     ])));
        // }

        //Se crea el fila en ArchiveRequiredDocument una vez realizado todo 
        $my_archive_required_document = ArchiveRequiredDocument::create([
            'archive_id' => $request->archive_id,
            'required_document_id' => $required_document_id,
            'location' => $path
        ]);

        //Se retorna una respueta SI SE PUDO O NO GUARDAR EL ARCHIVO
        return new JsonResponse(RecommendationLetter::create([
            'rl_id' => $request->recommendation_letter_id,
            'required_document_id' => $my_archive_required_document->id,
            'location' => $path
        ]), 200);
    }
}
