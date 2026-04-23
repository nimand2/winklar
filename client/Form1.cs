using System;
using System.Collections.Generic;
using System.Drawing;
using System.Globalization;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace SiusClient
{
    public partial class Form1 : Form
    {
        private readonly HttpClient _httpClient = new HttpClient();
        private readonly JsonSerializerOptions _jsonOptions = new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true
        };

        private string _authToken = string.Empty;
        private Anlass _gewaehlterAnlass;
        private CancellationTokenSource _backgroundCts;

        private readonly HashSet<long> _bereitsImportierteLogEvents = new HashSet<long>();
        private readonly HashSet<string> _bereitsImportierteZeilen = new HashSet<string>();

        private int _letzteImportierteZeilenAnzahl = 0;
        private int _anzahlGeleseneSchussdaten = 0;
        private int _anzahlSchuetzen = 0;
        private int _hoechsteSchuetzennummer = 0;
        private string _uhrzeitLetzterSchuss = "-";
        private int _letzteServerShooterId = 0;

        public Form1()
        {
            InitializeComponent();

            _httpClient.DefaultRequestHeaders.Accept.Clear();
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));

            btnOrdnerImport.Click += btnOrdnerImport_Click;
            btnOrdnerExport.Click += btnOrdnerExport_Click;
            btnVerbindungStarten.Click += btnVerbindungStarten_Click;
            btnAnlassWählen.Click += btnAnlassWählen_Click;

            tbPasswort.UseSystemPasswordChar = true;
            btnVerbindungStarten.UseVisualStyleBackColor = false;

            SetVerbindungsStatus(VerbindungsStatus.NichtVerbunden);
            UIStatistikenAktualisieren();
        }

        public class Anlass
        {
            public int Id { get; set; }
            public string Name { get; set; } = string.Empty;
        }

        public class LoginRequest
        {
            public string Username { get; set; } = string.Empty;
            public string Password { get; set; } = string.Empty;
        }

        public class LoginResponse
        {
            public string Token { get; set; } = string.Empty;
            public int ExpiresIn { get; set; }
        }

        public class ShooterDto
        {
            public int Id { get; set; }
            public int Startnummer { get; set; }
            public string Name { get; set; } = string.Empty;
            public string Vorname { get; set; } = string.Empty;
            public string Verein { get; set; } = string.Empty;
            public int Bahn { get; set; }
            public int Abloesung { get; set; }
            public bool Aktiv { get; set; }
        }

        public class ShotImportDto
        {
            public int AnlassId { get; set; }
            public List<SiusShotRecord> Shots { get; set; } = new List<SiusShotRecord>();
        }

        public class SiusShotRecord
        {
            public int StartNr { get; set; }
            public decimal Primaerwertung { get; set; }
            public int Schussart { get; set; }
            public int BahnNr { get; set; }
            public decimal Sekundaerwertung { get; set; }
            public int Teiler { get; set; }
            public string Zeit { get; set; } = string.Empty;
            public int Mouche { get; set; }
            public decimal X { get; set; }
            public decimal Y { get; set; }
            public int InTime { get; set; }
            public decimal TimeSinceChange { get; set; }
            public int SweepDirection { get; set; }
            public int Demonstration { get; set; }
            public int Match { get; set; }
            public int Stich { get; set; }
            public int InsDel { get; set; }
            public int TotalArt { get; set; }
            public int Gruppe { get; set; }
            public int Feuerart { get; set; }
            public long LogEvent { get; set; }
            public int LogTyp { get; set; }
            public long ZeitSeitJahresbeginn { get; set; }
            public int Abloesung { get; set; }
            public int Waffe { get; set; }
            public int Position { get; set; }
            public int TargetId { get; set; }
            public int ExterneNummer { get; set; }
        }

        private void btnOrdnerImport_Click(object sender, EventArgs e)
        {
            using (OpenFileDialog ofd = new OpenFileDialog())
            {
                ofd.Title = "Import-Datei auswählen";
                ofd.Filter = "CSV-Dateien (*.csv)|*.csv|Alle Dateien (*.*)|*.*";
                ofd.InitialDirectory = Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments);
                ofd.Multiselect = false;

                if (ofd.ShowDialog() == DialogResult.OK)
                {
                    tbImportDatei.Text = ofd.FileName;
                }
            }
        }

        private void btnOrdnerExport_Click(object sender, EventArgs e)
        {
            using (SaveFileDialog sfd = new SaveFileDialog())
            {
                sfd.Title = "Export-Datei auswählen";
                sfd.Filter = "CSV-Dateien (*.csv)|*.csv|Alle Dateien (*.*)|*.*";
                sfd.InitialDirectory = Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments);
                sfd.FileName = "Shooters.csv";

                if (sfd.ShowDialog() == DialogResult.OK)
                {
                    tbExportDatei.Text = sfd.FileName;
                }
            }
        }

        private async void btnVerbindungStarten_Click(object sender, EventArgs e)
        {
            await MitServerVerbindenAsync();
        }

        private async void btnAnlassWählen_Click(object sender, EventArgs e)
        {
            await AnlassAuswaehlenAsync();
        }

        private async Task MitServerVerbindenAsync()
        {
            string serverAdresse = tbServerAdresse.Text.Trim().TrimEnd('/');
            string benutzername = tbBenutzername.Text.Trim();
            string passwort = tbPasswort.Text;

            if (!EingabenSindGueltig(serverAdresse, benutzername, passwort))
            {
                return;
            }

            try
            {
                btnVerbindungStarten.Enabled = false;
                SetVerbindungsStatus(VerbindungsStatus.Verbindet);

                string loginUrl = $"{serverAdresse}/api/login";

                var loginRequest = new LoginRequest
                {
                    Username = benutzername,
                    Password = passwort
                };

                string json = JsonSerializer.Serialize(loginRequest);
                HttpResponseMessage response;
                using (var content = new StringContent(json, Encoding.UTF8, "application/json"))
                {
                    response = await _httpClient.PostAsync(loginUrl, content);
                }

                if (!response.IsSuccessStatusCode)
                {
                    SetVerbindungsStatus(VerbindungsStatus.NichtVerbunden);
                    string fehlerText = await response.Content.ReadAsStringAsync();

                    MessageBox.Show(
                        "Login fehlgeschlagen.\n\n" +
                        "Statuscode: " + (int)response.StatusCode + "\n" +
                        "Antwort: " + fehlerText,
                        "Fehler",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Error);
                    return;
                }

                string responseText = await response.Content.ReadAsStringAsync();

                try
                {
                    var loginResponse = JsonSerializer.Deserialize<LoginResponse>(responseText, _jsonOptions);
                    _authToken = loginResponse?.Token ?? responseText.Trim('"');
                }
                catch
                {
                    _authToken = responseText.Trim('"');
                }

                _httpClient.DefaultRequestHeaders.Authorization =
                    new AuthenticationHeaderValue("Bearer", _authToken);

                SetVerbindungsStatus(VerbindungsStatus.Verbunden);

                await AnlaesseVomServerLadenAsync();

                MessageBox.Show(
                    "Verbindung erfolgreich hergestellt und Anlässe geladen.",
                    "Erfolg",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                SetVerbindungsStatus(VerbindungsStatus.NichtVerbunden);

                MessageBox.Show(
                    "Fehler bei der Serververbindung:\n\n" + ex.Message,
                    "Verbindungsfehler",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error);
            }
            finally
            {
                btnVerbindungStarten.Enabled = true;
            }
        }

        private async Task AnlaesseVomServerLadenAsync()
        {
            string serverAdresse = tbServerAdresse.Text.Trim().TrimEnd('/');
            string url = $"{serverAdresse}/api/anlaesse";

            HttpResponseMessage response = await _httpClient.GetAsync(url);
            response.EnsureSuccessStatusCode();

            string json = await response.Content.ReadAsStringAsync();
            var anlaesse = JsonSerializer.Deserialize<List<Anlass>>(json, _jsonOptions) ?? new List<Anlass>();

            cbAnlass.DataSource = null;
            cbAnlass.DataSource = anlaesse;
            cbAnlass.DisplayMember = "Name";
            cbAnlass.ValueMember = "Id";
        }

        private async Task AnlassAuswaehlenAsync()
        {
            Anlass anlass = cbAnlass.SelectedItem as Anlass;
            if (anlass == null)
            {
                MessageBox.Show("Bitte zuerst einen Anlass auswählen.");
                return;
            }

            _gewaehlterAnlass = anlass;
            lblAnlass.Text = anlass.Name;

            await SchuetzenVomServerExportierenAsync(anlass.Id);

            HintergrundprozessStoppen();
            HintergrundprozessStarten();

            MessageBox.Show(
                $"Anlass \"{anlass.Name}\" wurde ausgewählt.\n\n" +
                "Schützen wurden exportiert und Hintergrundüberwachung gestartet.",
                "Anlass ausgewählt",
                MessageBoxButtons.OK,
                MessageBoxIcon.Information);
        }

        private async Task SchuetzenVomServerExportierenAsync(int anlassId)
        {
            string serverAdresse = tbServerAdresse.Text.Trim().TrimEnd('/');
            string url = $"{serverAdresse}/api/anlaesse/{anlassId}/shooters";

            HttpResponseMessage response = await _httpClient.GetAsync(url);
            response.EnsureSuccessStatusCode();

            string json = await response.Content.ReadAsStringAsync();
            var shooters = JsonSerializer.Deserialize<List<ShooterDto>>(json, _jsonOptions) ?? new List<ShooterDto>();

            var csvZeilen = new List<string>
            {
                "Id;Startnummer;Name;Vorname;Verein;Bahn;Abloesung;Aktiv"
            };

            foreach (var s in shooters)
            {
                csvZeilen.Add(string.Join(";",
                    s.Id,
                    s.Startnummer,
                    CsvEscape(s.Name),
                    CsvEscape(s.Vorname),
                    CsvEscape(s.Verein),
                    s.Bahn,
                    s.Abloesung,
                    s.Aktiv ? 1 : 0));
            }

            File.WriteAllLines(tbExportDatei.Text.Trim(), csvZeilen, Encoding.UTF8);

            _anzahlSchuetzen = shooters.Count;
            _hoechsteSchuetzennummer = shooters.Count == 0 ? 0 : shooters.Max(x => x.Startnummer);
            _letzteServerShooterId = shooters.Count == 0 ? 0 : shooters.Max(x => x.Id);

            UIStatistikenAktualisieren();
        }

        private void HintergrundprozessStarten()
        {
            _backgroundCts = new CancellationTokenSource();
            _ = HintergrundLoopAsync(_backgroundCts.Token);
        }

        private void HintergrundprozessStoppen()
        {
            if (_backgroundCts != null)
            {
                _backgroundCts.Cancel();
                _backgroundCts.Dispose();
                _backgroundCts = null;
            }
        }

        private async Task HintergrundLoopAsync(CancellationToken cancellationToken)
        {
            while (!cancellationToken.IsCancellationRequested)
            {
                try
                {
                    await ImportDateiPruefenUndSendenAsync();
                    await NeueSchuetzenVomServerPruefenAsync();
                }
                catch (Exception ex)
                {
                    BeginInvoke(new Action(() =>
                    {
                        MessageBox.Show(
                            "Fehler im Hintergrundprozess:\n\n" + ex.Message,
                            "Hintergrundfehler",
                            MessageBoxButtons.OK,
                            MessageBoxIcon.Warning);
                    }));
                }

                try
                {
                    await Task.Delay(TimeSpan.FromSeconds(3), cancellationToken);
                }
                catch (TaskCanceledException)
                {
                    break;
                }
            }
        }

        private async Task ImportDateiPruefenUndSendenAsync()
        {
            if (_gewaehlterAnlass == null)
                return;

            string pfad = tbImportDatei.Text.Trim();
            if (!File.Exists(pfad))
                return;

            string[] alleZeilen = File.ReadAllLines(pfad, Encoding.UTF8);

            if (alleZeilen.Length <= _letzteImportierteZeilenAnzahl)
                return;

            var neueZeilen = alleZeilen
                .Skip(_letzteImportierteZeilenAnzahl)
                .Where(z => !string.IsNullOrWhiteSpace(z))
                .ToList();

            var shotsZumSenden = new List<SiusShotRecord>();

            foreach (string zeile in neueZeilen)
            {
                if (_bereitsImportierteZeilen.Contains(zeile))
                    continue;

                if (TryParseSiusShotRecord(zeile, out var shot))
                {
                    // Laut SIUS-Format ist LogEvent eine aufsteigende Nummer und gut als Duplikat-Check geeignet.
                    if (shot.LogEvent > 0)
                    {
                        if (_bereitsImportierteLogEvents.Contains(shot.LogEvent))
                            continue;

                        _bereitsImportierteLogEvents.Add(shot.LogEvent);
                    }

                    _bereitsImportierteZeilen.Add(zeile);
                    shotsZumSenden.Add(shot);

                    _anzahlGeleseneSchussdaten++;
                    _uhrzeitLetzterSchuss = shot.Zeit;
                }
            }

            _letzteImportierteZeilenAnzahl = alleZeilen.Length;

            if (shotsZumSenden.Count == 0)
            {
                UIStatistikenAktualisierenThreadSafe();
                return;
            }

            string serverAdresse = tbServerAdresse.Text.Trim().TrimEnd('/');
            string url = $"{serverAdresse}/api/anlaesse/{_gewaehlterAnlass.Id}/shots/import";

            var payload = new ShotImportDto
            {
                AnlassId = _gewaehlterAnlass.Id,
                Shots = shotsZumSenden
            };

            string json = JsonSerializer.Serialize(payload);
            StringContent content = new StringContent(json, Encoding.UTF8, "application/json");
            try
            {
                HttpResponseMessage response = await _httpClient.PostAsync(url, content);
                response.EnsureSuccessStatusCode();
            }
            finally
            {
                content.Dispose();
            }

            UIStatistikenAktualisierenThreadSafe();
        }

        private async Task NeueSchuetzenVomServerPruefenAsync()
        {
            if (_gewaehlterAnlass == null)
                return;

            string serverAdresse = tbServerAdresse.Text.Trim().TrimEnd('/');
            string url = $"{serverAdresse}/api/anlaesse/{_gewaehlterAnlass.Id}/shooters/new?sinceId={_letzteServerShooterId}";

            HttpResponseMessage response = await _httpClient.GetAsync(url);

            if (!response.IsSuccessStatusCode)
                return;

            string json = await response.Content.ReadAsStringAsync();
            var neueShooter = JsonSerializer.Deserialize<List<ShooterDto>>(json, _jsonOptions) ?? new List<ShooterDto>();

            if (neueShooter.Count == 0)
                return;

            string exportPfad = tbExportDatei.Text.Trim();
            if (!File.Exists(exportPfad))
            {
                File.WriteAllLines(exportPfad, new[] { "Id;Startnummer;Name;Vorname;Verein;Bahn;Abloesung;Aktiv" }, Encoding.UTF8);
            }

            var vorhandeneZeilen = new HashSet<string>(File.ReadAllLines(exportPfad, Encoding.UTF8));
            var neueCsvZeilen = new List<string>();

            foreach (var s in neueShooter)
            {
                string csv = string.Join(";",
                    s.Id,
                    s.Startnummer,
                    CsvEscape(s.Name),
                    CsvEscape(s.Vorname),
                    CsvEscape(s.Verein),
                    s.Bahn,
                    s.Abloesung,
                    s.Aktiv ? 1 : 0);

                // Nur Nummer + Name darf doppelt sein, darum prüfen wir die ganze Zeile.
                if (!vorhandeneZeilen.Contains(csv))
                {
                    neueCsvZeilen.Add(csv);
                    vorhandeneZeilen.Add(csv);
                }
            }

            if (neueCsvZeilen.Count > 0)
            {
                File.AppendAllLines(exportPfad, neueCsvZeilen, Encoding.UTF8);
            }

            _anzahlSchuetzen += neueCsvZeilen.Count;

            int maxStartnummerNeue = neueShooter.Count == 0 ? 0 : neueShooter.Max(x => x.Startnummer);
            if (maxStartnummerNeue > _hoechsteSchuetzennummer)
            {
                _hoechsteSchuetzennummer = maxStartnummerNeue;
            }

            _letzteServerShooterId = neueShooter.Max(x => x.Id);

            UIStatistikenAktualisierenThreadSafe();
        }

        private bool TryParseSiusShotRecord(string zeile, out SiusShotRecord shot)
        {
            shot = new SiusShotRecord();

            // SIUS Schussformat hat 28 Felder.
            // Reihenfolge gemäss Doku: StartNr, Primärwertung, Schussart, Bahn Nr, Sekundärwertung, Teiler, Zeit, ...
            // Das ist die Beschreibung aus der SIUS-Dokumentation. :contentReference[oaicite:2]{index=2}
            string[] teile = zeile.Split(';');

            if (teile.Length < 28)
                return false;

            try
            {
                shot.StartNr = ParseInt(teile[0]);
                shot.Primaerwertung = ParseDecimal(teile[1]);
                shot.Schussart = ParseInt(teile[2]);
                shot.BahnNr = ParseInt(teile[3]);
                shot.Sekundaerwertung = ParseDecimal(teile[4]);
                shot.Teiler = ParseInt(teile[5]);
                shot.Zeit = teile[6];
                shot.Mouche = ParseInt(teile[7]);
                shot.X = ParseDecimal(teile[8]);
                shot.Y = ParseDecimal(teile[9]);
                shot.InTime = ParseInt(teile[10]);
                shot.TimeSinceChange = ParseDecimal(teile[11]);
                shot.SweepDirection = ParseInt(teile[12]);
                shot.Demonstration = ParseInt(teile[13]);
                shot.Match = ParseInt(teile[14]);
                shot.Stich = ParseInt(teile[15]);
                shot.InsDel = ParseInt(teile[16]);
                shot.TotalArt = ParseInt(teile[17]);
                shot.Gruppe = ParseInt(teile[18]);
                shot.Feuerart = ParseInt(teile[19]);
                shot.LogEvent = ParseLong(teile[20]);
                shot.LogTyp = ParseInt(teile[21]);
                shot.ZeitSeitJahresbeginn = ParseLong(teile[22]);
                shot.Abloesung = ParseInt(teile[23]);
                shot.Waffe = ParseInt(teile[24]);
                shot.Position = ParseInt(teile[25]);
                shot.TargetId = ParseInt(teile[26]);
                shot.ExterneNummer = ParseInt(teile[27]);

                return true;
            }
            catch
            {
                return false;
            }
        }

        private static int ParseInt(string value)
        {
            return int.Parse(value.Trim(), CultureInfo.InvariantCulture);
        }

        private static long ParseLong(string value)
        {
            return long.Parse(value.Trim(), CultureInfo.InvariantCulture);
        }

        private static decimal ParseDecimal(string value)
        {
            value = value.Trim().Replace(",", ".");
            return decimal.Parse(value, CultureInfo.InvariantCulture);
        }

        private static string CsvEscape(string value)
        {
            if (string.IsNullOrWhiteSpace(value))
                return "";

            return value.Replace(";", ",").Trim();
        }

        private bool EingabenSindGueltig(string serverAdresse, string benutzername, string passwort)
        {
            if (string.IsNullOrWhiteSpace(serverAdresse))
            {
                MessageBox.Show("Bitte Server-Adresse eingeben.");
                return false;
            }

            if (!serverAdresse.StartsWith("http://") && !serverAdresse.StartsWith("https://"))
            {
                MessageBox.Show("Bitte gib die Server-Adresse mit http:// oder https:// ein.");
                return false;
            }

            if (string.IsNullOrWhiteSpace(benutzername))
            {
                MessageBox.Show("Bitte Benutzernamen eingeben.");
                return false;
            }

            if (string.IsNullOrWhiteSpace(passwort))
            {
                MessageBox.Show("Bitte Passwort eingeben.");
                return false;
            }

            if (string.IsNullOrWhiteSpace(tbImportDatei.Text.Trim()))
            {
                MessageBox.Show("Bitte zuerst eine Import-Datei auswählen.");
                return false;
            }

            if (string.IsNullOrWhiteSpace(tbExportDatei.Text.Trim()))
            {
                MessageBox.Show("Bitte zuerst eine Export-Datei auswählen.");
                return false;
            }

            return true;
        }

        private void SetVerbindungsStatus(VerbindungsStatus status)
        {
            switch (status)
            {
                case VerbindungsStatus.NichtVerbunden:
                    btnVerbindungStarten.BackColor = Color.Red;
                    btnVerbindungStarten.Text = "Verbindung Starten";
                    break;

                case VerbindungsStatus.Verbindet:
                    btnVerbindungStarten.BackColor = Color.Orange;
                    btnVerbindungStarten.Text = "Verbinde...";
                    break;

                case VerbindungsStatus.Verbunden:
                    btnVerbindungStarten.BackColor = Color.LightGreen;
                    btnVerbindungStarten.Text = "Verbunden";
                    break;
            }
        }

        private void UIStatistikenAktualisieren()
        {
            lblAnzahlSchuezen.Text = _anzahlSchuetzen.ToString();
            lblHoechsteSchuezennummer.Text = _hoechsteSchuetzennummer.ToString();
            lblAnzahlGeleseneSchussdaten.Text = _anzahlGeleseneSchussdaten.ToString();
            lblUhrzeitLetzterSchuss.Text = _uhrzeitLetzterSchuss;
        }

        private void UIStatistikenAktualisierenThreadSafe()
        {
            if (InvokeRequired)
            {
                BeginInvoke(new Action(UIStatistikenAktualisieren));
                return;
            }

            UIStatistikenAktualisieren();
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            HintergrundprozessStoppen();
            _httpClient.Dispose();
            base.OnFormClosing(e);
        }
    }

    public enum VerbindungsStatus
    {
        NichtVerbunden,
        Verbindet,
        Verbunden
    }
}
