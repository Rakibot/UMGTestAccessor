using System;
using Acr.UserDialogs;
using System.Threading.Tasks;
using System.Collections.Generic;
using Xamarin.Forms;
using UMGTestAccessor.API;
using UMGTestAccessor.Model;
using FFImageLoading.Forms;
using FFImageLoading;

namespace UMGTestAccessor.Pages
{
    public class AlumnStatus : ContentPage
    {
        public const string DEBIT = "CON ADEUDOS";
        public const string NO_DEBIT = "SIN ADEUDOS";
        public const string NO_ALUMN = "NO ES ALUMNO";
        public const string DEBIT_MESSAGE = "Pase a tesoreria a aclarar su situación";
        public const string NO_ALUMN_MESSAGE = "En caso de ser alumno vaya a escolares para aclarar la situación";
        public const int OPEN_TIME = 5_000;

        public const uint TEXT_COLOR = 0xffffffff;
        public const int CODE_SIZE = 30;
        public const int NAME_SIZE = 35;
        public const int ERROR_SIZE = 40;
        public const int MESSAGE_SIZE = 18;

        public const uint COLOR_OK = 0xffc0df16;
        public const uint COLOR_ERROR = 0xffc00d1e;
        public const uint COLOR_WORKING = 0x0ff469bc6;
        public const string NO_IMAGE_ALUMN = "no_name";
        public const int IMAGE_SIZE = 200;

        private CachedImage alumnImage;
        private Label code;
        private Label name;
        private Label error;
        private Label message;

        public AlumnStatus(string code)
        {
            GetAlumn(code);


            alumnImage = new CachedImage()
            {
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                Source = NO_IMAGE_ALUMN,
                HeightRequest = IMAGE_SIZE,
                WidthRequest = IMAGE_SIZE,
                ErrorPlaceholder = NO_IMAGE_ALUMN,
                RetryCount = 0
                
            };
            this.code = new Label()
            {
                TextColor = Color.FromUint(TEXT_COLOR),
                FontSize = CODE_SIZE,
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                HorizontalTextAlignment = TextAlignment.Center,
                FontAttributes = FontAttributes.Bold
            };
            name = new Label()
            {
                TextColor = Color.FromUint(TEXT_COLOR),
                FontSize = NAME_SIZE,
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                HorizontalTextAlignment = TextAlignment.Center,
                FontAttributes = FontAttributes.Bold
            };
            error = new Label()
            {
                TextColor = Color.FromUint(TEXT_COLOR),
                FontSize = ERROR_SIZE,
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                HorizontalTextAlignment = TextAlignment.Center,
                FontAttributes = FontAttributes.Bold
            };
            message = new Label()
            {
                TextColor = Color.FromUint(TEXT_COLOR),
                FontSize = MESSAGE_SIZE,
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                HorizontalTextAlignment = TextAlignment.Center
            };


            Content = new ScrollView()
            {
                HorizontalOptions = LayoutOptions.FillAndExpand,
                VerticalOptions = LayoutOptions.FillAndExpand,
                Padding = new Thickness(20),
                Content = new StackLayout()
                {
                    HorizontalOptions = LayoutOptions.FillAndExpand,
                    VerticalOptions = LayoutOptions.FillAndExpand,
                    Children =
                    {
                        alumnImage,
                        this.code,
                        name,
                        error,
                        message
                    }
                }
            };


            BackgroundColor = Color.FromUint(COLOR_WORKING);
        }

        public async void GetAlumn(string code)
        {
            Device.BeginInvokeOnMainThread(() =>
            {
                UserDialogs.Instance.ShowLoading("");
            });
            GZCoreResponce<Alumn> alumn = await GZCore.Instance.Get<Alumn>("_Auth/canTakeExam", new Dictionary<string, string>() {
                { "code", code },
                { "uuid", Guid.NewGuid().ToString() } });
            
            if (alumn.Status.Code == 1 && alumn.Responce.Count > 0)
            {
                UpdateInformation(alumn.Responce[0]);
            }
            else if(alumn.Status.Code <= 0){
                Navigation.PushModalAsync(new ErrorPage());
                return;
            }
            else
            {
                UpdateInformation(new Alumn() { Debit = true , NotFound = true});
            }
            Device.BeginInvokeOnMainThread(() =>
            {
                UserDialogs.Instance.HideLoading();
            });
        }

        public void UpdateInformation(Alumn alumn)
        {
            Device.BeginInvokeOnMainThread(() =>
            {
                BackgroundColor = alumn.Debit ? Color.FromUint(COLOR_ERROR) : Color.FromUint(COLOR_OK);
                code.Text = alumn.Registration;
                name.Text = alumn.Name;
                error.Text = !alumn.Debit ? NO_DEBIT : alumn.NotFound? NO_ALUMN: DEBIT;
                message.Text = !alumn.Debit ? "" : (alumn.Registration == null) ? NO_ALUMN_MESSAGE : DEBIT_MESSAGE;

                if (alumn.Level != null)
                {
                    alumnImage.Source = App.Instance.GetImage(alumn.Level, alumn.Registration);
                }
                Task.Run(() =>
                {
                    Task.Delay(OPEN_TIME).Wait();
                    Device.BeginInvokeOnMainThread(() =>
                    {
                        Navigation.PopModalAsync();
                        //Navigation.PushModalAsync(new BarCodeReader());
                    });
                });

            });
        }
    }
}