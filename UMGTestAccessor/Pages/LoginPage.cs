using System;
using Acr.UserDialogs;
using UMGTestAccessor.API;
using Xamarin.Forms;

namespace UMGTestAccessor.Pages
{
    public class LoginPage : ContentPage
    {
        public const int LOGO_SIZE = 200;
        public const int FIELDS_SIZE = 200;
        public const int RADIUS = 20;
        public const int DIAMETER = RADIUS * 2;
        public const string LOGO_NAME = "logomarista";
        public const uint TEXT_COLOR = AlumnStatus.TEXT_COLOR;
        public const uint BACKGROUND_COLOR = AlumnStatus.COLOR_WORKING;
        public const string CODE_PLACE_HOLDER = "Codigo de activación";
        public const string CODE_TITLE_TEXT = "Código de verificación";
        public const string LOGIN_TEXT = "Ingresar";
        public const string INFO_TEXT = "Si aún no tiene el código de verificación pongase en contacto con su coordinador";

        private bool isLoging = false;
        private Entry code = new Entry
        {
            Placeholder = CODE_PLACE_HOLDER,
            WidthRequest = FIELDS_SIZE,
            HeightRequest = DIAMETER,
            HorizontalOptions = LayoutOptions.CenterAndExpand,
            IsPassword = true
        };
        public LoginPage()
        {
            BackgroundColor = Color.FromUint(BACKGROUND_COLOR);
            NavigationPage.SetHasNavigationBar(this, false);
            Button login = new Button()
            {
                Text = LOGIN_TEXT,
                CornerRadius = RADIUS,
                WidthRequest = FIELDS_SIZE,
                HeightRequest = DIAMETER,
                HorizontalOptions = LayoutOptions.CenterAndExpand,
                BackgroundColor = Color.White,
                BorderColor = Color.White,
                TextColor = Color.Black

            };
            login.Clicked += Logged;
            Content = new ScrollView()
            {
                HorizontalOptions = LayoutOptions.FillAndExpand,
                VerticalOptions = LayoutOptions.FillAndExpand,
                Padding = new Thickness(0),
                Content = new StackLayout
                {
                    Children = {
                        new Image(){
                            WidthRequest = LOGO_SIZE,
                            HorizontalOptions = LayoutOptions.CenterAndExpand,
                            Source = LOGO_NAME
                        },
                        new Label {
                            Text = CODE_TITLE_TEXT,
                            HorizontalOptions = LayoutOptions.FillAndExpand,
                            HorizontalTextAlignment = TextAlignment.Center,
                            TextColor = Color.FromUint(TEXT_COLOR)
                        },
                        code,
                        login,
                        new Label()
                        {
                            Text = INFO_TEXT,
                            FontSize = 13,
                            HorizontalOptions = LayoutOptions.CenterAndExpand,
                            WidthRequest = FIELDS_SIZE + 10
                        }
                    }
                }
            };
        }
        private async void Logged(object sender, EventArgs args)
        {
            if (!isLoging)
            {
                Device.BeginInvokeOnMainThread(() =>
                {
                    UserDialogs.Instance.ShowLoading("");
                });
                isLoging = true;
                for (int i = 0; i < GZCore.MAX_LOGIN_REQUEST; i++)
                {
#if DEBUG
                    Console.WriteLine("Loging");
#endif
                    bool loged = await GZCore.Instance.LogIn(code.Text);
#if DEBUG
                    Console.WriteLine("Loged: " + loged);
#endif
                    if (loged)
                    {
                        await Navigation.PushModalAsync(new BarCodePage());
                        break;
                    }
                }
                isLoging = false;
                Device.BeginInvokeOnMainThread(() =>
                {
                    UserDialogs.Instance.HideLoading();
                });
            }
        }
    }
}

