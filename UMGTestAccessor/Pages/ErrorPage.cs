using System;
using Xamarin.Forms;

namespace UMGTestAccessor.Pages
{
    public class ErrorPage: ContentPage
    {
        public const uint COLOR_UMG = AlumnStatus.COLOR_WORKING;
        public const uint COLOR_ERROR = AlumnStatus.COLOR_ERROR;
        public const uint COLOR_TEXT = AlumnStatus.TEXT_COLOR;
        public const string ERROR_TEXT = "Sin conexión a internet, es necesaria la conexión a internet para correr está aplicación cierre e intente más tarde";

        public ErrorPage()
        {
            NavigationPage.SetHasNavigationBar(this,false);
            BackgroundColor = Color.FromUint(COLOR_UMG);
            Content = new ScrollView()
            {
                HorizontalOptions = LayoutOptions.FillAndExpand,
                VerticalOptions = LayoutOptions.FillAndExpand,
                Padding = new Thickness(0, 20, 0, 20),
                Content = new StackLayout()
                {
                    HorizontalOptions = LayoutOptions.FillAndExpand,
                    VerticalOptions = LayoutOptions.FillAndExpand,
                    BackgroundColor = Color.FromUint(COLOR_ERROR),
                    Children =
                    {
                        new Label()
                        {
                            TextColor = Color.FromUint(COLOR_TEXT),
                            Text = ERROR_TEXT,
                            VerticalOptions = LayoutOptions.CenterAndExpand,
                            HorizontalOptions = LayoutOptions.CenterAndExpand,
                            HorizontalTextAlignment = TextAlignment.Center
                        }
                    }
                }
            };
        }
        protected override bool OnBackButtonPressed()
        {
            return true;
        }
    }
}
