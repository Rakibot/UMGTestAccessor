using System;
using System.IO;
using System.Collections.Generic;
using System.Linq;
using UMGTestAccessor.IO;
using Foundation;
using UIKit;

namespace UMGTestAccessor.iOS
{
    // The UIApplicationDelegate for the application. This class is responsible for launching the 
    // User Interface of the application, as well as listening (and optionally responding) to 
    // application events from iOS.
    [Register("AppDelegate")]
    public partial class AppDelegate : global::Xamarin.Forms.Platform.iOS.FormsApplicationDelegate, ILocalFileWriter
    {
        private string GetFolder()
        {
            string[] directories = NSSearchPath.GetDirectories(NSSearchPathDirectory.DocumentDirectory, NSSearchPathDomain.User);
            if (directories.Length > 0)
            {
                string directory = directories[0];
                return directory;
            }
            return "";
        }
        public bool Exists(string fileName)
        {
            NSFileManager manager = NSFileManager.DefaultManager;
            return manager.FileExists(Path.Join(GetFolder(), fileName));
        }

        //
        // This method is invoked when the application has loaded and is ready to run. In this 
        // method you should instantiate the window, load the UI into it and then make the window
        // visible.
        //
        // You have 17 seconds to return from this method, or iOS will terminate your application.
        //
        public override bool FinishedLaunching(UIApplication app, NSDictionary options)
        {
            /*
            AddObserver(UIApplication.UserDidTakeScreenshotNotification,
                NSKeyValueObservingOptions.New ,
                (action) =>
            {
                Console.WriteLine("ScreenShoot");
            });*/
            /*
            NSNotificationCenter.DefaultCenter.AddObserver(UIApplication.UserDidTakeScreenshotNotification, (notification) =>
            {
                Console.WriteLine("ScreenShoot");
            });*/

            global::Xamarin.Forms.Forms.Init();
            global::ZXing.Net.Mobile.Forms.iOS.Platform.Init();
            global::FFImageLoading.Forms.Platform.CachedImageRenderer.Init();
            LoadApplication(new App(this));

            return base.FinishedLaunching(app, options);
        }

        public string Read(string fileName)
        {
            string content = File.ReadAllText(Path.Join(GetFolder(),fileName));
            return content;
        }

        public void Write(string fileName, string content)
        {
            File.WriteAllText(Path.Join(GetFolder(), fileName),content);
        }

        
    }
}
