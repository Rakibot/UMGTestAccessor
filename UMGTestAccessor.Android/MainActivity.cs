using System;
using Java.IO;
using Acr.UserDialogs;
using Android.App;
using Android.Content.PM;
using Android.Runtime;
using Android.Views;
using Android.Widget;
using Android.OS;
using UMGTestAccessor.IO;

namespace UMGTestAccessor.Droid
{
    [Activity(Label = "UMGTestAccessor", Icon = "@drawable/icon", Theme = "@style/MainTheme", MainLauncher = true, ConfigurationChanges = ConfigChanges.ScreenSize | ConfigChanges.Orientation , ScreenOrientation = ScreenOrientation.Portrait)]
    public class MainActivity : global::Xamarin.Forms.Platform.Android.FormsAppCompatActivity,ILocalFileWriter
    {
        protected override void OnCreate(Bundle savedInstanceState)
        {/*
            try
            {*/

            Window.SetFlags(WindowManagerFlags.Secure, WindowManagerFlags.Secure);
            TabLayoutResource = Resource.Layout.Tabbar;
            ToolbarResource = Resource.Layout.Toolbar;

            base.OnCreate(savedInstanceState);

            Xamarin.Essentials.Platform.Init(this, savedInstanceState);
            global::Xamarin.Forms.Forms.Init(this, savedInstanceState);
            global::ZXing.Net.Mobile.Forms.Android.Platform.Init();
            global::FFImageLoading.Forms.Platform.CachedImageRenderer.Init(true);
            LoadApplication(new App(this));
            UserDialogs.Init(this);
            
            /*}
            catch (Exception e)
            {
                System.Console.WriteLine("Error: " + e);
            }*/
        }
        public override void OnRequestPermissionsResult(int requestCode, string[] permissions, [GeneratedEnum] Android.Content.PM.Permission[] grantResults)
        {
            Xamarin.Essentials.Platform.OnRequestPermissionsResult(requestCode, permissions, grantResults);
            global::ZXing.Net.Mobile.Android.PermissionsHandler.OnRequestPermissionsResult(requestCode, permissions, grantResults);
            base.OnRequestPermissionsResult(requestCode, permissions, grantResults);
        }

        public void Write(string fileName, string content)
        {
            File file = new File(FilesDir, fileName);
            System.IO.File.WriteAllText(file.AbsolutePath, content);
        }

        public string Read(string fileName)
        {
            File file = new File(FilesDir, fileName);

            if (file.Exists())
            {
                return System.IO.File.ReadAllText(file.AbsolutePath);
            }
            else
            {
                return null;
            }
        }

        public bool Exists(string fileName)
        {
            File file = new File(FilesDir, fileName);
            return file.Exists();
        }
    }
}