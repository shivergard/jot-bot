(function() {

	'use strict';

	angular
		.module('authApp')
		.controller('AuthController', AuthController);


	function AuthController($auth, $state, $http, $rootScope, $location) {

		var vm = this;

		if ( $location.search()) {
			var search = $location.search();
			for (var key in search) {
				vm[key] = search[key];
				$location.search(key, null);
			}
			if ( vm.first_name && vm.last_name ) {
				vm.name = vm.first_name + ' ' + vm.last_name;
			}
			if (vm.token) {
				$auth.setToken(vm.token);
				$http.get('api/authenticate/user').then(function(response) {

				// Stringify the returned data to prepare it
				// to go into local storage
				var user = JSON.stringify(response.data.user);

				// Set the stringified user data into local storage
				localStorage.setItem('user', user);

				// The user's authenticated state gets flipped to
				// true so we can now show parts of the UI that rely
				// on the user being logged in
				$rootScope.authenticated = true;

				// Putting the user's data on $rootScope allows
				// us to access it anywhere across the app
				$rootScope.currentUser = response.data.user;

				// Everything worked out so we can now redirect to
				// the users state to view the data
				$state.go('users');
			});
			}
		}

		vm.loginError = false;
		vm.loginErrorText;

		vm.login = function() {

			var credentials = {
				email: vm.email,
				password: vm.password
			}

			$auth.login(credentials).then(function() {

				// Return an $http request for the now authenticated
				// user so that we can flatten the promise chain
				return $http.get('api/authenticate/user');

			// Handle errors
			}, function(error) {
				vm.loginError = true;
				vm.loginErrorText = error.data.error;

			// Because we returned the $http.get request in the $auth.login
			// promise, we can chain the next promise to the end here
			}).then(function(response) {

				// Stringify the returned data to prepare it
				// to go into local storage
				var user = JSON.stringify(response.data.user);

				// Set the stringified user data into local storage
				localStorage.setItem('user', user);

				// The user's authenticated state gets flipped to
				// true so we can now show parts of the UI that rely
				// on the user being logged in
				$rootScope.authenticated = true;

				// Putting the user's data on $rootScope allows
				// us to access it anywhere across the app
				$rootScope.currentUser = response.data.user;

				// Everything worked out so we can now redirect to
				// the users state to view the data
				$state.go('users');
			});
		}

		vm.register = function() {
			var credentials = {
				name: vm.name,
				email: vm.email,
				password: vm.password,
				provider: vm.provider,
				provider_id: vm.provider_id,
				provider_token: vm.provider_token
			};

			$auth.signup(credentials).then(function(response) {
				return $http.get('api/authenticate/user');
			}, function(response) {
				var errors = response.data;
				for (var firstError in errors) break;
				vm.loginError = true;
				vm.loginErrorText = errors[firstError][0];
				return response;
			}).then(function(response) {
				if (! response.hasOwnProperty('data') || ! response.data.hasOwnProperty('user')) {
					return;
				}
				// Stringify the returned data to prepare it
				// to go into local storage
				var user = JSON.stringify(response.data.user);

				// Set the stringified user data into local storage
				localStorage.setItem('user', user);

				// The user's authenticated state gets flipped to
				// true so we can now show parts of the UI that rely
				// on the user being logged in
				$rootScope.authenticated = true;

				// Putting the user's data on $rootScope allows
				// us to access it anywhere across the app
				$rootScope.currentUser = response.data.user;

				// Everything worked out so we can now redirect to
				// the users state to view the data
				$state.go('users');
			});
		}

	}

})();