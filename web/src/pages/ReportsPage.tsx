import { useState } from "react";
import { PageContainer } from "../components/ui/PageContainer";
import {
  LoadingOverlay,
  TextInput,
  Alert,
  Select,
  Button,
  Stack,
  Group,
  Paper,
  Text,
  Badge,
  SimpleGrid,
  Title,
} from "@mantine/core";
import { SectionCard } from "../components/ui/SectionCard";
import { useTranslation } from "react-i18next";
import { useCategories, useCreateReport, useDownloadReport, useReports } from "../hooks/api";
import { extractApiError } from "../services/api";
import type { Report } from "../types/api";

const statusColors: Record<Report["status"], string> = {
  pending: "gray",
  processing: "blue",
  completed: "green",
  failed: "red",
};

function formatReportDate(value: string): string {
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime())
    ? value
    : parsed.toLocaleString();
}

export const ReportsPage = () => {
  const { t } = useTranslation();
  const [month, setMonth] = useState<string>("");
  const [type, setType] = useState<string>("");
  const [categoryId, setCategoryId] = useState<string>("");
  const [format, setFormat] = useState<"csv" | "pdf">("csv");
  const [error, setError] = useState<string>("");
  const [success, setSuccess] = useState<string>("");

  const { data: categories = [] } = useCategories();
  const { data: reportsResponse, isLoading } = useReports();
  const createReport = useCreateReport();
  const downloadReport = useDownloadReport();

  const reports = reportsResponse?.data ?? [];

  const handleGenerate = async () => {
    setError("");
    setSuccess("");
    try {
      await createReport.mutateAsync({
        format,
        month: month || undefined,
        type: type || undefined,
        category_id: categoryId ? Number(categoryId) : undefined,
      });
      setSuccess(t("reports.generate_success"));
    } catch (err) {
      setError(extractApiError(err).message);
    }
  };

  const handleDownload = async (report: Report) => {
    setError("");
    try {
      await downloadReport.mutateAsync({
        id: report.id,
        filename: `report-${report.id}.${report.format}`,
      });
    } catch (err) {
      setError(extractApiError(err).message);
    }
  };

  if (isLoading) {
    return (
      <PageContainer>
        <LoadingOverlay visible={isLoading} overlayProps={{ radius: "sm", blur: 2 }} />
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <Title order={1} mb="lg">
        {t("reports.title")}
      </Title>

      <SectionCard mb="lg">
        <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
          <TextInput
            label={t("transactions.period")}
            type="month"
            value={month}
            onChange={(e) => setMonth(e.target.value)}
          />

          <Select
            label={t("transactions.type")}
            value={type}
            onChange={(val) => setType(val ?? "")}
            data={[
              { value: "", label: t("transactions.all_types") },
              { value: "entrada", label: t("categories.type_income") },
              { value: "saida", label: t("categories.type_expense") },
            ]}
          />

          <Select
            label={t("transactions.category")}
            value={categoryId}
            onChange={(val) => setCategoryId(val ?? "")}
            data={categories.map((cat) => ({
              value: String(cat.id),
              label: cat.name,
            }))}
            clearable
          />

          <Select
            label={t("reports.format")}
            value={format}
            onChange={(val) => setFormat(val === "pdf" ? "pdf" : "csv")}
            data={[
              { value: "csv", label: t("reports.format_csv") },
              { value: "pdf", label: t("reports.format_pdf") },
            ]}
          />
        </SimpleGrid>

        <Button onClick={handleGenerate} loading={createReport.isPending}>
          {t("reports.generate")}
        </Button>
      </SectionCard>

      {error && (
        <Alert color="red" mb="md" radius="md">
          {error}
        </Alert>
      )}
      {success && (
        <Alert color="green" mb="md" radius="md">
          {success}
        </Alert>
      )}

      <Text fw={600} mb="sm">
        {t("reports.history")}
      </Text>

      {reports.length === 0 ? (
        <Text c="dimmed" ta="center" py="xl" fs="italic">
          {t("reports.empty")}
        </Text>
      ) : (
        <Stack gap="sm">
          {reports.map((report) => (
            <Paper key={report.id} shadow="xs" radius="md" p="md" withBorder>
              <Group justify="space-between" align="center">
                <Stack gap={2}>
                  <Group gap="xs" align="center">
                    <Text fw={600}>{report.format.toUpperCase()}</Text>
                    <Badge color={statusColors[report.status]} variant="light">
                      {t(`reports.status_${report.status}`)}
                    </Badge>
                  </Group>
                  <Text size="xs" c="dimmed">
                    {t("reports.created_at")}: {formatReportDate(report.created_at)}
                  </Text>
                  {report.status === "failed" && report.failure_reason && (
                    <Text size="xs" c="red">
                      {t("reports.failure_reason")}: {report.failure_reason}
                    </Text>
                  )}
                </Stack>

                {report.status === "completed" && (
                  <Button
                    size="xs"
                    variant="light"
                    loading={downloadReport.isPending}
                    onClick={() => handleDownload(report)}
                    leftSection={<i className="fa-solid fa-download" aria-hidden="true" />}
                  >
                    {t("reports.download")}
                  </Button>
                )}
              </Group>
            </Paper>
          ))}
        </Stack>
      )}
    </PageContainer>
  );
};
