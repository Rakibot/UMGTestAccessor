using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Xamarin.Forms;
using UMGTestAccessor.Pages;
using UMGTestAccessor.API;

namespace UMGTestAccessor
{
    // Learn more about making custom code visible in the Xamarin.Forms previewer
    // by visiting https://aka.ms/xamarinforms-previewer
    [DesignTimeVisible(false)]
    public partial class MainPage : ContentPage
    {
        public MainPage()
        {
            NavigationPage.SetHasNavigationBar(this, false);
            BackgroundColor = Color.FromUint(0xff469BC6);
            InitializeComponent();
            //Navigation.PushAsync(new BarCodeReader());
        }

        protected override void OnAppearing()
        {
            base.OnAppearing();
            Validate();
        }

        private async void Validate()
        {
            try
            {
                GZCoreResponce<Dictionary<string, string>> fotos = await GZCore.Instance.Get<Dictionary<string, string>>("umg_derex_fotos", null);
                Console.WriteLine("Fotos: " + fotos);
                App.Instance.SetImages(fotos.Responce);
            }catch(Exception e)
            {
                Console.WriteLine("Error: " + e);
            }

            GZCoreResponce<bool> isValid = await GZCore.Instance.ValidateSession();
            Console.WriteLine("IsValid: " + isValid + " Status: "+isValid.Status+" Code: "+isValid.Status.Code);

            if (isValid != null && isValid.Status.Code > 0)
            {
                if (isValid.Status.Code == 1 && isValid.Responce[0])
                {
                    Navigation.PushAsync(new BarCodePage());
                }
                else
                {
                    Navigation.PushAsync(new LoginPage());
                }
            }
            else
            {
                Navigation.PushAsync(new ErrorPage());
                
            }
        }
        protected override void OnDisappearing()
        {
            base.OnDisappearing();
            Navigation.RemovePage(this);

        }
    }
}
