using System;
using Newtonsoft.Json;
namespace UMGTestAccessor.Model
{
    public class Alumn
    {
        [JsonProperty("Id")]
        public int Id { get; set; }
        [JsonProperty("Matricula")]
        public string Registration { get; set; }
        [JsonProperty("Nombre")]
        public string Name { get; set; }
        [JsonProperty("Nivel")]
        public string Level { get; set; }
        [JsonProperty("Adeudo")]
        public bool Debit { get; set; }
        [JsonIgnore]
        public bool NotFound { get; set; }
    }
}
