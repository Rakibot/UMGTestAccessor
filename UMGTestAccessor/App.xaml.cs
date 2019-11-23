using System;
using System.Collections.Generic;
using Xamarin.Forms;
using UMGTestAccessor.API;
using UMGTestAccessor.IO;

namespace UMGTestAccessor
{
    public partial class App : Application
    {
        private const string TOKEN_FILE = "token.jwt";
        private ILocalFileWriter fileWriter;
        private Dictionary<string, string> images = new Dictionary<string, string>();

        public static App Instance { get; private set; }

        public App(ILocalFileWriter fileWriter)
        {
            Instance = this;
            this.fileWriter = fileWriter;
            InitializeComponent();

            if (fileWriter.Exists(TOKEN_FILE))
            {
                string token = fileWriter.Read(TOKEN_FILE);
                GZCore.RestoreInstance(token);
            }

            MainPage = new NavigationPage(new MainPage());
        }

        public void SetImages(List<Dictionary<string,string>> images)
        {
            foreach(Dictionary<string,string> image in images)
            {
                this.images[image["Nivel"]] = image["Ruta"];
            }
        }

        public string GetImage(string level, string code)
        {
            string res = (images.ContainsKey(level)) ? images[level] : "";

            res += $"{code}.jpg";

            return res;
        }

        protected override void OnStart()
        {
            // Handle when your app starts
        }

        protected override void OnSleep()
        {
            Console.WriteLine("OnSleep");
            GZCore core = GZCore.Instance;

            fileWriter.Write(TOKEN_FILE, core.Token);
        }

        protected override void OnResume()
        {
            // Handle when your app resumes
        }
    }
}
