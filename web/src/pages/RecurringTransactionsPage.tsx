import { useState } from "react";
import { useTranslation } from "react-i18next";
import {
  LoadingOverlay,
  Alert,
  Stack,
  Group,
  Flex,
  Paper,
  Text,
  Badge,
  Title,
  Button,
} from "@mantine/core";
import { PageContainer } from "../components/ui/PageContainer";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { useCancelRecurringTransaction, useRecurringTransactions } from "../hooks/api";
import { extractApiError } from "../services/api";
import { getCategoryIconClass } from "../constants/categoryIcons";
import type { RecurringTransaction } from "../types/api";

const statusColors: Record<RecurringTransaction["status"], string> = {
  active: "green",
  cancelled: "gray",
};

function formatDate(value?: string | null): string {
  if (!value) return "-";
  const datePart = value.slice(0, 10);
  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split("-").map(Number);
    return new Date(year, month - 1, day).toLocaleDateString();
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

export function RecurringTransactionsPage() {
  const { t } = useTranslation();
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [confirmTarget, setConfirmTarget] = useState<RecurringTransaction | null>(null);

  const { data: rules = [], isLoading } = useRecurringTransactions();
  const cancelRecurringTransaction = useCancelRecurringTransaction();

  const handleCancel = async () => {
    if (!confirmTarget) return;
    setError("");
    setSuccess("");
    try {
      await cancelRecurringTransaction.mutateAsync(confirmTarget.id);
      setSuccess(t("recurring.cancel_success"));
    } catch (err) {
      setError(extractApiError(err).message);
    } finally {
      setConfirmTarget(null);
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
        {t("recurring.title")}
      </Title>

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

      {rules.length === 0 ? (
        <Text c="dimmed" ta="center" py="xl" fs="italic">
          {t("recurring.empty")}
        </Text>
      ) : (
        <Stack gap="sm" >
          {rules.map((rule) => (
            <Paper key={rule.id} shadow="xs" radius="md" p="md" withBorder >
              <Flex
                justify="space-between"
                direction={{ base: "column", sm: "row" }}
                gap="sm"
              >
                <Stack
                  gap={2}
                >
                  <Group gap="xs" align="center">
                    <Text
                      fw={600}
                      style={{ display: "inline-flex", alignItems: "center" }}
                    >
                      <i
                        className={getCategoryIconClass(rule.category?.icon)}
                        aria-hidden="true"
                        style={{ marginRight: "0.5rem" }}
                      />
                      {rule.category?.name}
                    </Text>
                    <Badge color={statusColors[rule.status]} variant="light">
                      {t(`recurring.status_${rule.status}`)}
                    </Badge>
                  </Group>
                  <Text size="xs" c="dimmed">
                    {t("recurring.day_of_month_label")}: {rule.day_of_month}
                  </Text>
                  <Text size="xs" c="dimmed">
                    {t("recurring.last_generated_label")}: {formatDate(rule.last_generated_at)}
                  </Text>
                  {rule.notes && (
                    <Text size="sm" c="dimmed">
                      {rule.notes}
                    </Text>
                  )}
                </Stack>

                <Group
                  gap="xs"
                  align="center"
                  justify="center"
                  wrap="nowrap"
                >
                  <Text
                    fw={700}
                    size="lg"
                    c={rule.type === "income" ? "green" : "red"}
                  >
                    {rule.type === "income" ? "+" : "-"} R${" "}
                    {Number(rule.amount).toFixed(2)}
                  </Text>
                  {rule.status === "active" && (
                    <Button
                      size="xs"
                      variant="light"
                      color="red"
                      onClick={() => setConfirmTarget(rule)}
                    >
                      {t("recurring.cancel_action")}
                    </Button>
                  )}
                </Group>
              </Flex>
            </Paper>
          ))}
        </Stack>
      )}

      <ConfirmDialog
        opened={confirmTarget !== null}
        title={t("recurring.cancel_confirm_title")}
        message={t("recurring.cancel_confirm_message")}
        onConfirm={handleCancel}
        onCancel={() => setConfirmTarget(null)}
        loading={cancelRecurringTransaction.isPending}
      />
    </PageContainer>
  );
}
