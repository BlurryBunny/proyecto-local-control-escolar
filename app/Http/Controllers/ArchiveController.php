<?php

namespace App\Http\Controllers;

# Peticiones
use App\Http\Requests\{
    AddScientificProductionAuthorRequest,
    UpdateAcademicDegreeRequest,
    UpdateAppliantLanguageRequest,
    UpdateHumanCapitalRequest,
    UpdateScientificProductionAuthorRequest,
    UpdateScientificProductionRequest,
    UpdateWorkingExperienceRequest,
    StoreRecommendationLetter,
    UpdateMailRecommendationLetter,
};

# Resources (respuestas en formato JSON)
use App\Http\Resources\AppliantArchive\ArchiveResource;
use App\Mail\SendRecommendationLetter;
//convertir blade a pdf
use Barryvdh\DomPDF\Facade\Pdf;

# Modelos
use App\Models\{
    AcademicArea,
    AcademicDegree,
    AcademicProgram,
    AppliantLanguage,
    Archive,
    ArchiveRequiredDocument,
    Author,
    CustomParameter,
    HumanCapital,
    MyRecommendationLetter,
    Parameter,
    ScientificProduction,
    User,
    WorkingExperience,
    RecommendationLetter,
    RequiredDocument,
    ScoreParameter,
};

# Clases auxiliares de Laravel.
use Illuminate\Http\{
    JsonResponse,
    Request,
    File
};

use Illuminate\Support\Facades\{
    DB,
    Schema,
    Cache,
    Mail,
    Storage
};

# Clases de otros paquetes.
use Spatie\QueryBuilder\{
    AllowedFilter,
    QueryBuilder
};

class ArchiveController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the archives dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * 
     */
    public function index(Request $request)
    {
        return view('postulacion.index')
            ->with('user', $request->user())
            ->with('academic_programs', AcademicProgram::with('latestAnnouncement')->get());
    }

    //agregar la carta de recomendacion con la informacion del usuario

    // public function recommendationLetter(Request $request){
    //     return view('postulacion.recommendation-letter')
    //     -> with('user', $request->user())
    //     ;  
    // }

    /**
     * Obtiene los expedientes, vía api.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * 
     */
    public function archives(Request $request)
    {
        $archives = QueryBuilder::for(Archive::class)
            ->with('appliant')
            ->allowedIncludes(['announcement'])
            ->allowedFilters([
                AllowedFilter::exact('announcement.id'),
            ])->get();

        return ArchiveResource::collection($archives);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function postulacion(Request $request, $archiveusr)
    {
        $archiveModel = Archive::where('id', $archiveusr)->first();
        if ($archiveModel == null)
            return 'no existe expediente para este aspirante';

        $archiveModel->loadMissing([
            'appliant',
            'announcement.academicProgram',
            'personalDocuments',
            // 'recommendationLetter',
            'myRecommendationLetter',
            'entranceDocuments',
            'intentionLetter',
            'academicDegrees.requiredDocuments',
            'appliantLanguages.requiredDocuments',
            'appliantWorkingExperiences',
            'scientificProductions.authors',
            'humanCapitals'
        ]);

        $academic_program = $archiveModel->announcement->academicProgram;
        $appliant = $archiveModel->appliant;

        # Recolecta el resultado.
        // dd($archiveModel);
        return view('postulacion.show')
            ->with('archive', $archiveModel)
            ->with('appliant', $appliant)
            ->with('academic_program', $academic_program);
    }


    /**
     * Envia la vista de carta de recomendación con los datos requeridos
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function recommendationLetter(Request $request, $user_id = 256079)
    {
        // Se extrae el archivo de postulacion
        $archiveModel = Archive::where('user_id', $user_id)->first();

        //Si no existe entonces NO HAY POSTULANTE que requiera carta
        if ($archiveModel == null)
            return view('postulacion.error-noAppliant');


        # Verifica la cantidad de cartas de recomendación otorgadas.
        $recommendation_letter_count = $archiveModel->archiveRequiredDocuments()
            ->whereNotNull('location')
            ->whereIsRecommendationLetter()
            ->count();

        // ya se ENTREGARON 2 CARTAS   
        if ($recommendation_letter_count >= 2) {
            return view('postulacion.error-lettersSent');
        }

        // Extrae TODOS LOS PARAMETROS A EVALUAR
        $parameters = Parameter::all();

        // se cartan los archivos perdidos
        $archiveModel->loadMissing([
            'appliant',
            'announcement.academicProgram',
            'myRecommendationLetter',
            'personalDocuments',
            'entranceDocuments',
            'academicDegrees.requiredDocuments',
            'appliantLanguages.requiredDocuments',
            'appliantWorkingExperiences',
            'scientificProductions.authors',
            'humanCapitals'
        ]);

        $announcement = $archiveModel->announcement;
        $appliant = $archiveModel->appliant;

        //    dd($archiveModel->myRecommendationLetter);
        //     dd($archiveModel->myRecommendationLetter->toJson()["id"]);
        // dd($recommendation_letter_count);
        // dd($archiveModel->appliant);

        // dd($archiveModel);
        return view('postulacion.recommendation-letter')
            ->with('idArchive', $archiveModel->id)
            ->with('archiveRl', $archiveModel->myRecommendationLetter)
            ->with('appliant', $appliant)                   //usuario 
            ->with('announcement', $announcement)
            ->with('parameters', $parameters)
            ->with('index', $recommendation_letter_count);  //programa academico

    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateMotivation(Request $request)
    {
        Archive::where('id', $request->archive_id)->update(['motivation' => $request->motivation]);

        return new JsonResponse(
            Archive::select('motivation')->firstWhere('id', $request->archive_id)
        );
    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateArchivePersonalDocument(Request $request)
    {
        $archive = Archive::find($request->archive_id);

        # Archivo de la solicitud

        $ruta = $request->file('file')->storeAs(
            'archives/' . $request->archive_id . '/personalDocuments',
            $request->requiredDocumentId . '.pdf'
        );

        # Asocia los documentos requeridos.
        $archive->personalDocuments()->detach($request->requiredDocumentId);
        $archive->personalDocuments()->attach($request->requiredDocumentId, ['location' => $ruta]);

        return new JsonResponse(
            $archive->personalDocuments()
                ->select('required_documents.*', 'archive_required_document.location as location')
                ->where('id', $request->requiredDocumentId)
                ->first()
        );
    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateArchiveEntranceDocument(Request $request)
    {
        $archive = Archive::find($request->archive_id);

        # Archivo de la solicitud
        $ruta = $request->file('file')->storeAs(
            'archives/' . $request->archive_id . '/entranceDocuments',
            $request->requiredDocumentId . '.pdf'
        );

        # Asocia los documentos requeridos.
        $archive->entranceDocuments()->detach($request->requiredDocumentId);
        $archive->entranceDocuments()->attach($request->requiredDocumentId, ['location' => $ruta]);
        /**Problema al regresar el json, marca un erro en la consulta */
        return new JsonResponse(
            $archive->entranceDocuments()
                ->select('required_documents.*', 'archive_required_document.location as location')
                ->where('id', $request->requiredDocumentId)
                ->first()
        );
    }

    /**
     * Obtiene el grado académico más reciente.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function latestAcademicDegree(Request $request, Archive $archive)
    {
        return new JsonResponse($archive->latestAcademicDegree);
    }

    /**
     * Actualiza los datos académicos del postulante.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateAcademicDegree(UpdateAcademicDegreeRequest $request)
    {
        $academic_degree = AcademicDegree::find($request->id);
        $academic_degree->fill($request->validated());
        $academic_degree->save();

        return new JsonResponse($academic_degree);
    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateAcademicDegreeRequiredDocument(Request $request)
    {
        //FUncion guarda el archivo y actualiza el archivo que se guardo al momento de ver,
        //Pero hasta que subes el archivo por 2 vez aparece el mensaje que se a guardado con exito

        $academic_degree = AcademicDegree::find($request->id);

        # Archivo de la solicitud
        $ruta = $request->file('file')->storeAs(
            'archives/' . $request->archive_id . '/academicDocuments',
            $request->requiredDocumentId . '.pdf'
        );

        # Asocia los documentos requeridos.
        $academic_degree->requiredDocuments()->detach($request->requiredDocumentId);
        $academic_degree->requiredDocuments()->attach($request->requiredDocumentId, ['location' => $ruta]);

        return new JsonResponse(
            $academic_degree->requiredDocuments()
                ->select('required_documents.*', 'academic_degree_required_document.location as location')
                ->where('id', $request->requiredDocumentId)
                ->first()
        );
    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateWorkingExperience(UpdateWorkingExperienceRequest $request)
    {
        WorkingExperience::where('id', $request->id)->update($request->safe()->toArray());

        return new JsonResponse(WorkingExperience::find($request->id));
    }

    /**
     * Actualiza un documento requerido, para el grado académico
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateAppliantLanguage(UpdateAppliantLanguageRequest $request)
    {
        AppliantLanguage::where('id', $request->id)->update($request->safe()->toArray());

        return new JsonResponse(AppliantLanguage::find($request->id));
    }


    /**
     * Actualiza la lengua extranjera de un postulante.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateAppliantLanguageRequiredDocument(Request $request)
    {
        $appliant_language = AppliantLanguage::find($request->id);

        # Archivo de la solicitud
        $ruta = $request->file('file')->storeAs(
            'archives/' . $request->archive_id . '/laguageDocuments/',
            $request->id . '_' . $request->requiredDocumentId . '.pdf'
        );

        # Asocia los documentos requeridos.
        $appliant_language->requiredDocuments()->detach($request->requiredDocumentId);
        $appliant_language->requiredDocuments()->attach($request->requiredDocumentId, ['location' => $ruta]);

        return new JsonResponse(
            $appliant_language->requiredDocuments()
                ->select('required_documents.*', 'appliant_language_required_document.location as location')
                ->where('id', $request->requiredDocumentId)
                ->first()
        );
    }

    /**
     * Actualiza la lengua extranjera de un postulante.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateScientificProduction(UpdateScientificProductionRequest $request)
    {
        $type = ScientificProduction::where('id', $request->id)->value('type');

        # Determina si el tipo de producción científica cambió
        # y borra la producción científica anterior.
        if ($type !== null && $type !== $request->type && Schema::hasTable($type)) {
            DB::table($type)->where('scientific_production_id', $request->id)->delete();
        }

        $upsert_array = [];
        $identifiers = ['scientific_production_id' => $request->id];

        switch ($request->type) {
            case 'articles':
                $upsert_array = ['magazine_name' => $request->magazine_name];
                break;
            case 'published_chapters':
                $upsert_array = ['article_name' => $request->article_name];
                break;
            case 'technical_reports':
                $upsert_array = ['institution' => $request->institution];
                break;
            case 'working_documents':
                $upsert_array = ['post_title' => $request->post_title];
                break;
            case 'working_memories':
                $upsert_array = ['post_title' => $request->post_title];
                break;
        }

        # Actualiza los datos adicionales de la producción científica.
        if (count($upsert_array) > 0)
            DB::table($request->type)->updateOrInsert($upsert_array, $identifiers);

        # Actualiza los datos generales de la producción científica.
        ScientificProduction::where('id', $request->id)
            ->update($request->safe()->only('state', 'title', 'publish_date', 'type'));

        return new JsonResponse(
            ScientificProduction::leftJoin(
                'articles',
                'articles.scientific_production_id',
                'scientific_productions.id'
            )->leftJoin(
                'published_chapters',
                'published_chapters.scientific_production_id',
                'scientific_productions.id'
            )->leftJoin(
                'technical_reports',
                'technical_reports.scientific_production_id',
                'scientific_productions.id'
            )->leftJoin(
                'working_documents',
                'working_documents.scientific_production_id',
                'scientific_productions.id'
            )->leftJoin(
                'working_memories',
                'working_memories.scientific_production_id',
                'scientific_productions.id'
            )->first()
        );
    }

    /**
     * Agrega un autor a la producción científica.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function addScientificProductionAuthor(AddScientificProductionAuthorRequest $request)
    {
        ScientificProduction::where('id', $request->scientific_production_id)->update($request->only('type'));

        return new JsonResponse(Author::create($request->safe()->only('scientific_production_id', 'name')));
    }

    public function sentEmailRecommendationLetter(Request $request, $email, $appliant)
    {
        Mail::to($email->validate(['required', 'integer'])->send(new SendRecommendationLetter($email,$appliant));
    }

    /**
     * Agrega la carta de recomendacion 
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function addRecommendationLetter(StoreRecommendationLetter $request)
    {
        # Crear archivo PDF
        // $recommendation_letter_pdf = PDF::loadView('pdf.recommendation-letter', Cache::get('recommendation_letter_info'));
        $recommendation_letter_pdf = PDF::loadView('pdf.recommendation-letter', $request);

        /*
        Se guarda el documento
        Se da el nombre de la ruta
        */

        # Se busca expediente, para asignar nombre
        $archive = Archive::find($request->archive_id);

        # Cartas de recomendacion en expediente
        $num_recommendation_letter_count = $archive->archiveRequiredDocuments()
            ->whereNotNull('location')
            ->whereIsRecommendationLetter()
            ->count();

        #Ids para relacion a archive required document table
        $required_document_id  = ($num_recommendation_letter_count > 0) ? 19 : 20;
            
        // $ruta = $recommendation_letter_pdf->file('file')->storeAs(
        //     'archives/' . $request->archive_id . '/recommendation-letter/',
        //     $request->id . '_' . $archive->user_id . '_' . $num_recommendation_letter_count . '_' . $required_document_id . '.pdf'
        // );

        return $recommendation_letter_pdf->stream();

        // #Separacion de informacion 

        // /* datos STRING para table de recomendacion*/
        // $data_rl_table = $request->safe()->except('score_parameters', 'custom_parameters');

        // // datos numericos para puntaje 
        // $data_score_table = $request->safe()->only('score_parameters');

        // //datos custom
        // $data_custom_parameter =  $request->safe()->only('custom_parameters');

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

        // # Todos los datos han sido insertados correctamente, ahora llamamos al guardado en PDF
        // if ($res_rec && $res_param && $res_cust_param) {
        //     storeRecommendationLetterRequiredDocument($request,$recommendation_letter->id);  //se manda carta
        // } else {
        //     return view('postulacion.error-letterSent'); //Faltaron campos se necesita crear de nuevo
        // }
    }


    /*
    Se reciben 
    Todos los datos de validacion 
    Id 
    */
    //     public function storeRecommendationLetterRequiredDocument(StoreRecommendationLetter $request, $rl_id)
    //     {
    //         /*
    //             La fila de archivo de carta de recomendacion se crea a partir de los datos de carta de recomendacion y el id  recibido 
    //             id del archivo de carta de recomendacion 
    //             id del archivo requerido
    //             */


    //             # Se busca expediente
    //             $archive = Archive::find($request->archive_id);

    //             # Cartas de recomendacion en expediente
    //             $num_recommendation_letter_count = $archiveModel->archiveRequiredDocuments()
    //             ->whereNotNull('location')
    //             ->whereIsRecommendationLetter()
    //             ->count();

    //             #Ids para relacion a archive required document table
    //             $required_document_id  = ($num_recommendation_letter_count>0)?19:20;
    //             $archive_id = $request->archive_id;

    //             #Se crea fila en la tabla de ArchiveRequiredDocument
    //             ArchiveRequiredDocument::create([$archive_id, $id_required_document]->validate(
    //                 'archive_id' => ['required', 'integer'],
    //                 'required_document_id' => ['required', 'integer']
    //             ));



    //             //se guardaran las rutas en required document y archive recommendation Letter
    //             $archive_require_doc_appliant = ArchiveRequiredDocument::find($request->archive_id);

    //         $recommendation_letter = RecommendationLetter::create({$request->id, $archive_id}); //id hacia el archivo 
    //         $required_document = RequiredDocument::find($request->requiredDocumentId); // la tabla de documentos requeridos



    //         # Asocia los documentos requeridos.
    //         $recommendation_letter->requiredDocuments()->attach($request->requiredDocumentId, ['location' => $ruta]);
    //         $recommendation_letter->location = $ruta;



    // # Verifica la cantidad de cartas de recomendación otorgadas.
    // $recommendation_letter_count = $archiveModel->archiveRequiredDocuments()
    //     ->whereNotNull('location')
    //     ->whereIsRecommendationLetter()
    //     ->count();

    // // ya se ENTREGARON 2 CARTAS   
    // if ($recommendation_letter_count >= 2) {
    //     return view('postulacion.error-lettersSent');
    // }

    // // Extrae TODOS LOS PARAMETROS A EVALUAR
    // $parameters = Parameter::all();

    // // se cartan los archivos perdidos
    // $archiveModel->loadMissing([
    //     'appliant',
    //     'announcement.academicProgram',
    //     'myRecommendationLetter',
    //     'personalDocuments',
    //     'entranceDocuments',
    //     'academicDegrees.requiredDocuments',
    //     'appliantLanguages.requiredDocuments',
    //     'appliantWorkingExperiences',
    //     'scientificProductions.authors',
    //     'humanCapitals'
    // ]);

    // $announcement = $archiveModel->announcement;
    // $appliant = $archiveModel->appliant;

    // //    dd($archiveModel->myRecommendationLetter);
    // //     dd($archiveModel->myRecommendationLetter->toJson()["id"]);
    // // dd($recommendation_letter_count);
    // // dd($archiveModel->appliant);

    // // dd($archiveModel);
    //         /*
    //         Se manda a llamar la vista que contiene aquello que se quiere guardar en el documento
    //         */

    //         $id_rl = $archiveModel->id;
    //         $rec_letter = $archiveModel->myRecommendationLetter;
    //         $recommendation_letter_pdf = PDF::loadView('postulacion.recommendation-letter', compact('id_rl'))
    //         ->with('idArchive', $archiveModel->id)
    //         ->with('archiveRl', $archiveModel->myRecommendationLetter)
    //         ->with('appliant', $appliant)                   //usuario 
    //         ->with('announcement', $announcement)
    //         ->with('parameters', $parameters)
    //         ->with('index', $recommendation_letter_count); 

    //         /*
    //         Se guarda el documento
    //         Se da el nombre de la ruta
    //         */
    //         $ruta = $pdf->storeAs(
    //             'archives/' . $request->archive_id . '/laguageDocuments/',
    //             $request->id . '_' . $required_document->required_document_id . '_' . $request->requiredDocumentId . '.pdf'
    //         );

    //         /*
    //         Se guarda el nombre de la ruta en las tablas

    //         Documento requerido
    //         Carta de recomendacion
    //         */

    //         return new JsonResponse(
    //             $recommendation_letter->requiredDocuments()
    //                 ->select('required_documents.*', 'appliant_language_required_document.location as location')
    //                 ->where('id', $request->requiredDocumentId)
    //                 ->first()
    //         );
    //     }



    /**
     * Actualiza un autor de una producción científica.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateScientificProductionAuthor(UpdateScientificProductionAuthorRequest $request)
    {
        ScientificProduction::where('id', $request->scientific_production_id)->update($request->only('type'));
        Author::where('id', $request->id)
            ->update($request->safe()->only('scientific_production_id', 'name'));

        return new JsonResponse(Author::find($request->id));
    }

    /**
     * Actualiza el capital humano de un postulante.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function updateHumanCapital(UpdateHumanCapitalRequest $request)
    {
        HumanCapital::where('id', $request->id)->update($request->validated());

        return new JsonResponse(HumanCapital::find($request->id));
    }
}
