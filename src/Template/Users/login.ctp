<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>React Test</title>

    <link href="<?php echo SITE_URL;?>login/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <script crossorigin src="https://unpkg.com/react@16/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@16/umd/react-dom.development.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

</head>
<body>
    <?= $this->Flash->render(); ?>
    <div id="app-root"></div>
    <script type="text/babel">
        // axios.get("http://localhost/yashwant/react_cake/api/v1/users/profile",{headers: {
        //             "Content-type": "application/json; charset=UTF-8",
        //             "authorization" : "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3lhc2h3YW50XC9yZWFjdF9jYWtlXC8iLCJhdWQiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3lhc2h3YW50XC9yZWFjdF9jYWtlXC8iLCJpYXQiOjE1ODU2NDA5MzEsImV4cCI6MTU4NTY0NDUzMSwidXNlcl9pZCI6MX0.jvVunbNFQ-843OIW-QXS1igRgoWYRqez7OALACp-ZJw"
        //             }}).then(res => console.log(res))
        //                 .catch(err => console.log(err));

        function App(){
            const [loginState, setLoginState] = React.useState({
                loading : false,
                data : {},
                error : ''
            });
            const [loginData, setLoginData] = React.useState({
                email : "yashwant@mailinator.com",
                password : "A4admin8",
                device_id : "sampledeviceId",
                device_type : "sampledevice_type"
            });

            // React.useEffect(() => {   
            // },[]);

            const handleInputChange = (e) => {
                setLoginData({...loginData, [e.target.name] : e.target.value});
            }

            const handleSubmit = (e) => {
                e.preventDefault();
                setLoginState({...loginState, loading:true});
                axios.post("http://localhost/yashwant/react_cake/api/v1/users/login",loginData)
                .then(res => {
                    setLoginState(prevState => {
                        return {
                            loading : false,
                            data: res.data,
                            error : ''
                        }
                    });
                    localStorage.setItem("token",res.data.token);
                    axios.post("http://localhost/yashwant/react_cake/Users/setSuccess",{
                        message : "Login successfully"
                    }).then(res => {
                        // window.location.href="<?= SITE_URL."Users/index"?>";  
                    }).catch(err => {
                        console.log(err);
                    });
                })
                .catch(err => {
                    setLoginState(prevState => {
                        return {
                            loading : false,
                            data : {},
                            error : err.response.data.message
                        }
                    });
                })
            }
            return (
                <React.Fragment>
                    <div className="row">
                        <div className="col-4 offset-4">
                            <div className="card">
                                <div className="card-header">
                                    <h3>Login</h3>
                                </div>
                                <div className="card-body">
                                    <form onSubmit={handleSubmit} id="loginForm">
                                        <div className="form-group">
                                            <label htmlFor="email">Email</label>
                                            <input type='text' name="email" placeholder="Email" className="form-control" onChange={handleInputChange} value={loginData.email}/>
                                        </div>  
                                        <div className="form-group">
                                            <label htmlFor="password">Password</label>
                                            <input type='password' name="password" placeholder="Password" className="form-control" onChange={handleInputChange} value={loginData.password}/>
                                        </div>
                                        <div className="form-group">
                                            <button type='submit'>Login</button>
                                        </div>
                                        <p><a href="<?= SITE_URL."users/forgot_password"?>">Forgot Password ?</a></p>
                                        <p>Don't have an account? <a href="<?= SITE_URL."users/signup"?>">Signup</a></p>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    {loginState.loading ? <h1>Loading...</h1> : loginState.error ? <h1>{loginState.error}</h1> : <div style={{maxWidth:"800px"}}><h1>{JSON.stringify(loginState.data)}</h1></div>}   
                </React.Fragment>
            )
        }
        ReactDOM.render(<App />, document.querySelector("#app-root"));
</script>
</body>
</html>