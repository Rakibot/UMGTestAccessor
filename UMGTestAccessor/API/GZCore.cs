using System;
using System.Collections.Generic;
using Newtonsoft.Json;
using System.Threading.Tasks;
using Flurl.Http;
using System.Net.Http;
using System.Net.Http.Headers;
using Xamarin.Forms;

namespace UMGTestAccessor.API
{
    public class GZCore
    {
#if DEBUG
        public const string SERVER_URL = "http://rakibot.com/demos/virgil/gzcore/index.php";
#else
        public const string SERVER_URL = "https://derechoexamen.marista.mx/gzcore/index.php";
#endif
        public const int SERVER_TIMEOUT = 60000;
        public const int MAX_LOGIN_REQUEST = 3;
        private string serverUrl;
        private static GZCore instance;
        public string Token { get; private set; }
        public static GZCore Instance
        {
            get
            {
                if(instance == null)
                {
                    instance = new GZCore();
                }
                return instance;
            }
        }
        public static GZCore CreateInstance(string serverUrl)
        {
            return new GZCore(serverUrl);
        }
        public static GZCore RestoreInstance(string token,string serverUrl = SERVER_URL)
        {
            var res = Instance;
            res.Token = token;

            Console.WriteLine("TokenToRestore: " + token);

            return res;
        }


        private GZCore(string serverUrl = SERVER_URL)
        {
            this.serverUrl = serverUrl;
        }


        public async Task<bool> LogIn(string code, int count = 0)
        {
            Dictionary<string, object> data = new Dictionary<string, object>();
            data["Codigo"] = code;
            GZCoreResponce<string> login = await Post<string>("_Auth/login", data);
            if(login.Status.Code == 1)
            {
                Token = login.Responce[0];
                return true;
            }
            if(login.Status.Code < 0 && count >= MAX_LOGIN_REQUEST)
            {
                Application.Current.MainPage.Navigation.PushAsync(new UMGTestAccessor.Pages.ErrorPage());
            }
            return false;
        }

        public async Task<GZCoreResponce<bool>> ValidateSession()
        {
            GZCoreResponce<bool> valid = await Post<bool>("_Auth/validateSession", new Dictionary<string, object>());
            return valid;
        }

        public async Task<GZCoreResponce<T>> Get<T>(string table, Dictionary<string,string> query)
        {
            try
            {
                string url = serverUrl + $"/{table}";
                if (query != null && query.Count > 0)
                {
                    url += "?";
                    foreach (KeyValuePair<string, string> entry in query)
                    {
                        url += $"{entry.Key}={entry.Value}&";
                    }
                    url = url.Substring(0, url.Length - 1);
                }
                Console.WriteLine("URL: " + url);
                IFlurlRequest request = url.WithTimeout(SERVER_TIMEOUT).AllowAnyHttpStatus();
                if (Token != null)
                {
                    Console.WriteLine("Token: " + Token);
                    request.WithHeader("Authorization", Token);
                }
                HttpResponseMessage response = await request.GetAsync();

                ProcessHeaders(response.Headers);

                string res = await response.Content.ReadAsStringAsync();

//#if DEBUG
                Console.WriteLine(res);
//#endif
                return JsonConvert.DeserializeObject<GZCoreResponce<T>>(res);
            }catch(Exception e)
            {
                Console.WriteLine("Error on GET: " + e);
                if (e is FlurlHttpException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = -2
                        },
                        Responce = new List<T>()
                    };
                }else if (e is FlurlHttpTimeoutException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = -3
                        },
                        Responce = new List<T>()
                    };
                }
                else if(e is JsonException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = 0
                        },
                        Responce = new List<T>()
                    };
                }
                return new GZCoreResponce<T>()
                {
                    Status = new GZCoreStatus()
                    {
                        Code = -1
                    },
                    Responce = new List<T>()
                };
            }

        }

        public async Task<GZCoreResponce<T>> Post<T>(string table, Dictionary<string,object> body)
        {
            try
            {
                string url = serverUrl + $"/{table}";
                Console.WriteLine("URL: " + url);
                IFlurlRequest request = url.WithTimeout(SERVER_TIMEOUT).AllowAnyHttpStatus();
                if (Token != null)
                {
                    request.WithHeader("Authorization", Token);
                }
                HttpResponseMessage responce = await request.PostJsonAsync(body);
                ProcessHeaders(responce.Headers);
                string res = await responce.Content.ReadAsStringAsync();

//#if DEBUG
                Console.WriteLine(res);
//#endif

                return JsonConvert.DeserializeObject<GZCoreResponce<T>>(res);
            }catch(Exception e)
            {
                Console.WriteLine("Error on POST: " + e);
                if(e is FlurlHttpException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = -2
                        },
                        Responce = new List<T>()
                    };
                }
                else if (e is FlurlHttpTimeoutException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = -3
                        },
                        Responce = new List<T>()
                    };
                }
                else if (e is JsonException)
                {
                    return new GZCoreResponce<T>()
                    {
                        Status = new GZCoreStatus()
                        {
                            Code = 0
                        },
                        Responce = new List<T>()
                    };
                }
                return new GZCoreResponce<T>()
                {
                    Status = new GZCoreStatus()
                    {
                        Code = -1
                    },
                    Responce = new List<T>()
                };
            }
        }
        private void ProcessHeaders(HttpResponseHeaders headers)
        {
            try
            {
                IEnumerable<string> tokenList;
                if (headers.Contains("Authorization"))
                {
                    tokenList = headers.GetValues("Authorization");
                }else if (headers.Contains("authorization"))
                {
                    tokenList = headers.GetValues("authorization");
                }
                else
                {
                    return;
                }
                foreach (string token in tokenList)
                {
                    if (token != null)
                    {
                        Token = token;
                    }
                }
            }catch(Exception e){
                Console.WriteLine("Errro processing headers: " + e);
            }
        }

    }
    public class GZCoreStatus
    {
        [JsonProperty("info_msg")]
        public string InfoMessage { get; set; }
        [JsonProperty("code")]
        public int Code { get; set; }
    }
    public class GZCoreResponce<T>
    {
        [JsonProperty("status")]
        public GZCoreStatus Status { get; set; }
        [JsonProperty("response")]
        public List<T> Responce { get; set; }
    }
}
