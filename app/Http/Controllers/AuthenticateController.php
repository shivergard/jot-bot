<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Laravel\Socialite\Facades\Socialite;

use App\Http\Requests\CreateUserRequest;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\Account;

class AuthenticateController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['authenticate', 'authenticateWith', 'authenticateCallback', 'register']]);
    }

    /**
     * Return the user
     *
     * @return Response
     */
    public function index()
    {

        $users = User::all();

        return $users;
    }

    /**
     * Return a JWT
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        // if no errors are encountered we can return a JWT
        return response()->json(compact('token'));
    }

    /**
     * Redirect to OAuth provider for logging in
     *
     * @return Response
     */
    public function authenticateWith($provider) {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * The OAuth callback
     */
    public function authenticateCallback($provider, Request $request) {
        // try to find the account who wants to login or register
        $social_user = Socialite::driver( $provider )->user();
        $social_account = Account::where( 'provider', $provider )->where( 'provider_id', $social_user->id )->first();

        // if the account exists, either answer with a redirect or return the access token
        // this decision is made when we are checking if the request is an AJAX request
        if( $social_account )
        {
            $user = $social_account->user;
            if ( ! $request->ajax() )
            {
                return redirect( env( 'FRONTED_URL' ) . '/#/auth?token=' . JWTAuth::fromUser( $user ), 302 );
            }
            return response( JWTAuth::fromUser( $user ), 200 );
        }

        // the account does not exist yet.

        // redirect to frontend, if user is coming per link, adding contents for form fields
        if ( ! $request->ajax() )
        {
            return redirect( env( 'FRONTED_URL' ) .
                '/#/auth?first_name=' . $social_user->user['first_name'] .
                '&last_name=' . $social_user->user['last_name'] .
                '&email=' . $social_user->user['email'] .
                '&gender=' . ( ( 'male' === $social_user->user['gender'] ) ? 'm' : 'f' ) .
                '&provider=' . $provider .
                '&provider_id=' . $social_user->id .
                '&provider_token=' . $social_user->token
                , 302 );
        }
        // otherwise return the data as json
        return response( array(
            'first_name' => $social_user->user['first_name'],
            'last_name' => $social_user->user['last_name'],
            'email' => $social_user->user['email'],
            'gender' => ( ( 'male' === $social_user->user['gender'] ) ? 'm' : 'f' ),
            'provider' => $provider,
            'provider_id' =>  $social_user->id,
            'provider_token' => $social_user->token,
        ), 200 );
    }

    /**
     * Registers an user
     * The password is encrypted and the user is stored.
     * If there are information from the social login, then create an account from them and link it to the user.
     *
     * @return Response
     */
    public function register( CreateUserRequest $request ) {
        // store the user in the database
        $credentials = $request->only( 'name', 'email', 'password');
        $credentials[ 'password' ] = bcrypt( $credentials[ 'password' ] );
        $user = User::create($credentials);

        // now wire up the provider account (e.g. facebook) to the user, if provided.
        if ( isset( $request['provider'] ) && isset( $request['provider_id'] ) && isset( $request['provider_token'] ) ) {
            $user->accounts()->save( new Account( [
                'provider' => $request['provider'],
                'provider_id' => $request['provider_id'],
                'access_token' => $request['provider_token'],
            ] ) );
        }

        // return the JWT to the user
        $token = JWTAuth::fromUser( $user );
        return response( compact( 'token' ), 200 );
    }

    /**
     * Return the authenticated user
     *
     * @return Response
     */
    public function getAuthenticatedUser()
    {
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }

        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }
}
