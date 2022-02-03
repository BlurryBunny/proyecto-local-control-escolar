<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoginService;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * El controlador consume al proovedor de servicios de Mi Portal.
     *
     * @var \App\Helpers\LoginService
     */
    private $loginService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except(['testLogin','logout']);
        $this->loginService = new LoginService;
    }

    /**
     * Recupera los usuarios del sistema autenticado.
     *
     * @param \Illuminate\Http\Request  $request
     * @param User $user
     * @return void
     */
    private function getUsers(Request $request, User $user)
    {
        # Guarda al usuario autenticado.
        $request->session()->put('user', $user);

        # Solo solicita los datos, siempre y cuando el usuario sea un postulante.
        //if (!$user->isWorker())
        //    return;

        # Carga otros datos que requiere el modelo.
        $user->load(['academicAreas', 'academicEntities']);

        # Busca a los postulantes.
        $appliants = User::with(['latestArchive.intentionLetters:archive_intention_letter.user_id,archive_intention_letter.user_type'])
            ->hasArchive()
            ->appliant()
            ->pluck('id');

        # Busca a los profesores en el sistema.
        $professors = User::role(['profesor_nb','admin','control_escolar','personal_apoyo'])->pluck('id');
        
        # Fusiona a los usuarios.
        $users = $professors->merge($appliants)->toArray();

        # Consulta a los usuarios.
        $response = $this->miPortalService->miPortalGet('api/usuarios', [
            'filter[userModules.id]' => env('MIPORTAL_MODULE_ID'),
            //'fields[users]' => 'id,name,middlename,surname,type,curp,email',
            'filter[id]' => $users
        ]);

        # Recolecta el resultado.
        $miPortal_appliants = $response->collect()->whereNotIn('user_type', ['workers']);
        $miPortal_workers = $response->collect()->where('user_type', 'workers');

        # Guarda a los usuarios del sistema central en la sesión.
        $request->session()->put('appliants', $miPortal_appliants);
        $request->session()->put('workers', $miPortal_workers);
    }

    public function prelogin(){
        if(!Auth::user()){
            return redirect(route('pre-registro.index'));
        }
        return redirect(route('home'));
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function login(LoginRequest $request)
    {
        //puse esta condicion para entrar en local, 
        //se debe quitar y descomentar la funcion de arriab despues
        if(!isset($request)){
            return redirect(route('pre-registro.index'));
        }else
        {

            # Determina si se requiere solicitar autorización.
            ///*
            if (!$this->loginService->isCallbackRequest($request))
                return $this->loginService->requestAuthorization($request);
            //*/

            # Busca al usuario en el sistema central.
            $user_response = $this->loginService->loginGet('api/users/whoami', $request->code);
            
            # Si la respuesta fue errónea, devuele el error.
            ///*
            if ($user_response->failed())
                return back()->withErrors($user_response->collect());
            //*/

            # Recolecta los datos del usuario.
            $miportal_user = $user_response->collect();

            # Busca al usuario en el sistema.
            $user = User::where('id', $miportal_user['id'])->where('type', $miportal_user['user_type'])->first();

            # Si el usuario no está en el sistema, manda error.
            ///*
            if ($user === null)
                return back()->withErrors(['motivo' => 'Usuario no registrado en el sistema']);
            //*/

            # Autentica al usuario y guarda los datos del sistema central.
            $miportal_user['roles'] = $user->roles;
            Auth::login($user);

            $this->getUsers($request, $user);
            
            }

        # Redirecciona a la página principal.
        return redirect()->route('authenticate.home');
    }

    /**
     * Handle a test login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function testLogin(Request $request, $user)
    {
        # Determina si se requiere solicitar autorización.
        Auth::loginUsingId($user);
        
        /** @var User */
        $user = Auth::user();
        $user->load('roles');
        $this->getUsers($request, $user);

        # Redirecciona a la página principal.
        return redirect()->route('authenticate.home');
    }
}