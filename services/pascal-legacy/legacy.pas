program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, Unix, StrUtils;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandBool(): Boolean;
begin
  Result := Random(2) = 1;
end;

procedure GenerateAndCopy();
var
  outDir, fn, fullpath, pghost, pgport, pguser, pgpass, pgdb, copyCmd: string;
  f: TextFile;
  ts: string;
  i: Integer;
  recordedAt: TDateTime;
  voltage, temp: Double;
  isActive: Boolean;
  sensorId: String;
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ts := FormatDateTime('yyyymmdd_hhnnss', Now);
  fn := 'telemetry_' + ts + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;

  // Генерируем несколько записей с правильными типами данных
  AssignFile(f, fullpath);
  Rewrite(f);
  
  // Заголовок CSV
  Writeln(f, 'recorded_at,voltage,temp,is_active,sensor_id,source_file');
  
  // Генерируем 10 записей с разными типами данных
  for i := 1 to 10 do
  begin
    recordedAt := Now - (i * 1.0 / 24.0); // Каждая запись на час раньше
    voltage := RandFloat(3.2, 12.6);
    temp := RandFloat(-50.0, 80.0);
    isActive := RandBool();
    sensorId := 'SENSOR_' + IntToStr(Random(1000) + 1);
    
    // Форматируем данные правильно:
    // - timestamp в формате ISO 8601
    // - числа без кавычек
    // - boolean как ИСТИНА/ЛОЖЬ
    // - строки в кавычках если содержат запятые
    Writeln(f, 
      FormatDateTime('yyyy-mm-dd"T"hh:nn:ss"Z"', recordedAt) + ',' +
      FormatFloat('0.00', voltage) + ',' +
      FormatFloat('0.00', temp) + ',' +
      IfThen(isActive, 'TRUE', 'FALSE') + ',' +
      sensorId + ',' +
      fn
    );
  end;
  
  CloseFile(f);

  // COPY into Postgres
  pghost := GetEnvDef('PGHOST', 'db');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'monouser');
  pgpass := GetEnvDef('PGPASSWORD', 'monopass');
  pgdb   := GetEnvDef('PGDATABASE', 'monolith');

  copyCmd := 'psql "host=' + pghost + ' port=' + pgport + ' user=' + pguser + ' dbname=' + pgdb + '" ' +
             '-c "\copy telemetry_legacy(recorded_at, voltage, temp, is_active, sensor_id, source_file) FROM ''' + fullpath + ''' WITH (FORMAT csv, HEADER true)"';
  
  // Устанавливаем PGPASSWORD через переменную окружения в команде
  copyCmd := 'PGPASSWORD=' + pgpass + ' ' + copyCmd;
  fpSystem(PChar(copyCmd));
  
  WriteLn('[Legacy] Generated: ', fn);
end;

var period: Integer;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);
  WriteLn('[Legacy] Starting CSV generator, period: ', period, ' seconds');
  while True do
  begin
    try
      GenerateAndCopy();
    except
      on E: Exception do
        WriteLn('[Legacy] Error: ', E.Message);
    end;
    Sleep(period * 1000);
  end;
end.
